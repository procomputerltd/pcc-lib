<?php
namespace Procomputer\Pcclib\DirectorySynchronizer;

/* 
 * Copyright (C) 2024 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Messages\Messages;

class DirectorySynchronizer {
    
    const DIFF = 'diff';
    const MISSING = 'missing';
    const DIRECTORIES = 'directories';

    use Messages;
    
    /**
     * 
     * @param string $directory1
     * @param string $directory2
     * @param array|string $extensionFilter
     * @param array|string $skippedDirectories
     * @return array|bool
     */
    public function compare(string $directory1, string $directory2, array|string $extensionFilter = ['php'], array|string $skippedDirectories = []) {
        $fileItems = [];
        $directories = [$directory1, $directory2];
        foreach($directories as $key => $directory) {
            $dirTrimmed = trim($directory);
            if(Types::isBlank($dirTrimmed)) {
                $msg = ($key ? 'secondary' : 'primary') . " directory parameter is empty";
            }
            elseif(! is_dir($dirTrimmed)) {
                $msg = ($key ? 'secondary' : 'primary') . " directory not found";
            }
            if(! empty($msg)) {
                $var = Types::getVarType($directory);
                $this->addMessage($msg . ". Expecting an existing file directory, got '{$var}'", 'danger', 'RUNTIME ERROR!');
                return false;
            }
            // Get the normalized/resolved real file path.
            $realpath = $this->_getDirRealPath($dirTrimmed);
            $directories[$key] = $realpath;
            $fileItems[] = $this->_scanDirectory($realpath, $key, $extensionFilter, $skippedDirectories);
        }
        if(count($fileItems[0]) && count($fileItems[1])) {
            $item0 = reset($fileItems[0]);
            $item1 = reset($fileItems[1]);
            if($item0->filepath === $item1->filepath) {
                $this->addMessage($msg = "cannot syncronize same directory: {$fileItems[0]->filepath}");
                return false;
            }
        }
        $items = [
            self::DIRECTORIES => $directories,
            self::DIFF => $this->_getDiff($fileItems, $directories)
        ];
        return $items;
    }
    
    private function _getDiff($fileItems, $directories) {
        $return = [[],[]];
        for($index = 0; $index < 2; $index++) {
            foreach($fileItems[$index] as $fileItem) {
                $fileNameHash = $fileItem->filenameHash();
                if(isset($fileItems[1 - $index][$fileNameHash])) {
                    if(! isset($return[$fileNameHash])) {
                        $fileItem2 = $fileItems[1 - $index][$fileNameHash];
                        // Compare the file data.
                        if($fileItem->diff($fileItem2)) {
                            // A file1 timestamp greater than file2 means file1 is recenly modified.
                            // Place the most recenly modified file path at index 0.
                            $fileItem->associateItem($fileItem2);
                            $return[0][$fileNameHash] = $fileItem;
                        }
                    }
                }
                else {
                    $fileItem->setCopyToDirectory($directories[1]);
                    $return[1][$fileNameHash] = $fileItem;
                }
            }
        }
        /*
        foreach($fileItems[1] as $fileItem) {
            $fileNameHash = $fileItem->filenameHash();
            if(! isset($fileItems[0][$fileNameHash])) {
                $fileItem->setCopyTo($directories[0]);
                $return[$fileNameHash] = $fileItem;
            }
        }
        */
        return array_merge($return[1], $return[0]);
    }
    
    /**
     * Synchronizes files collected by compare()
     * @param array $fileItems
     * @param array $selectedItems
     * @return int|bool Returns the number of files copied or false on error.
     * @throws \RuntimeException
     */
    public function synchronize(array $fileItems, array $selectedItems) {
        if(! count($selectedItems)) {
            $this->addMessage("No files were selected. Start over.", 'warning', 'NOTICE:');
            return false;
        }
        $syncItems = [];
        $diffItems = $fileItems['diff'];
        foreach($selectedItems as $hash) {
            if(! isset($diffItems[$hash])) {
                $this->addMessage("The REQUEST value of one of the selected files is not found in the file items: {$hash}", 'danger', 'RUNTIME ERROR!');
                return false;
            }
            $syncItems[] = $diffItems[$hash];
        }
        $copied = 0;
        /** @var FileItem $fileItem */
        foreach($syncItems as $fileItem) {
            $result = $fileItem->synchronize();
            if(false === $result) {
                $this->addMessage($fileItem->lastError, 'danger', 'RUNTIME ERROR!');
                return false;
            }
            elseif($result) {
                $copied++;
            }
        }
        return $copied;
    }

    private function _scanDirectory(string $directory, int $index, array|string $extensionFilter = [], array|string $skippedDirectories = []) {
        $items = [];
        $skippedDirs = [];
        foreach((array)$skippedDirectories as $key => $dir) {
            if(is_string($dir) && strlen($dir = trim($dir))) {
                $skippedDirs[$key] = str_replace('\\', '/', strtolower($dir));
            }
        }
        if(empty($skippedDirs)) {
            $skippedDirs = false;
        }
        $dirTrimmed = trim($directory);
        if(Types::isBlank($dirTrimmed)) {
            $msg = "\$directory parameter is empty";
        }
        elseif(! is_dir($dirTrimmed)) {
            $msg = "\$directory parameter is not a directory";
        }
        if(! empty($msg)) {
            $var = Types::getVarType($directory);
            $msg .= ". Expection an existing file directory. Got '{$var}'";
            throw new \InvalidArgumentException($msg);
        }
        // Get the normalized/resolved real file path.
        $realpath = $this->_getDirRealPath($dirTrimmed);
        $filter = [];
        if(is_string($extensionFilter)) {
            $extensionFilter = trim($extensionFilter);
            if(! empty($extensionFilter) && '*' !== $extensionFilter) {
                $filter = [$extensionFilter];
            }
        }
        else {
            foreach($extensionFilter as $item) {
                if(is_string($item)) {
                    $item = trim($item);
                    if(strlen($item)) {
                        $filter[] = $item;
                    }
                }
            }
        }
        // Create recursive directory iterator
        $fileIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($realpath), \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach($fileIterator as $iteratorItem) {
            /* @var $iteratorItem \Iterator */
            // Skip directories
            if($iteratorItem->isDir()) {
                continue;
            }
            // Get the normalized/resolved real file path.
            $filePath = $iteratorItem->getRealPath();
            // Ensure the file path dirname matches the source dirname.
            if(0 !== strpos($filePath, $realpath)) {
                throw new \RuntimeException("directory path mismatch: '{$filePath}' and '{$realpath}'");
            }
            // Get relative path for current file
            $subDir = substr($filePath, strlen($realpath));
            if($skippedDirs) {
                $path = str_replace('\\', '/', strtolower($subDir));
                foreach($skippedDirs as $dir) {
                    if(0 === strpos($path, $dir)) {
                        continue 2;
                    }
                }
            }
            /* PHP's pathinfo() returns an array:
                [dirname]   => c:\temp
                [basename]  => base.foo.bar
                [extension] => bar
                [filename]  => base.foo */
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if(count($filter) && false === array_search($extension, $filter)) {
                continue;
            }
            $item = new FileItem($filePath, $subDir, $index);
            $items[$item->filenameHash()] = $item;
        }
        return $items;
    }

    /**
     * Attempt to get an absolute (real) path for the given directory.
     * @param string $directory
     * @return string
     */
    private function _getDirRealPath(string $directory) {
        $dir = $this->normalizeFilePath($directory);
        if(! strlen($dir) || ! is_dir($dir)) {
            throw new \InvalidArgumentException("cannot get directory real path: the directory parameter is empty or not a directory: " . $directory);
        }
        $fileIterator = new \DirectoryIterator($dir);
        foreach($fileIterator as $iteratorItem) {
            /* @var $iteratorItem \Iterator */
            if($iteratorItem->isDot()) {
                continue;
            }
            // Get real and relative path for current file
            // $file = $iteratorItem->getFilename();
            $file = $iteratorItem->getRealPath();
            $path = pathinfo($file, PATHINFO_DIRNAME) ;
            return $path;
        }
        $file = $dir . DIRECTORY_SEPARATOR . sha1(__CLASS__) . '.tmp';
        $new = $this->_createFile($file);
        if(! $new) {
            $path = $dir;
        }
        else {
            $this->_deleteFile($new);
            $path = pathinfo($new, PATHINFO_DIRNAME);
        }
        return $this->normalizeFilePath($path);
    }
    
    private function _createFile(string $file) {
        $phpErrorHandler = new PhpErrorHandler();
        $handle = $phpErrorHandler->call(function()use($file){
            return fopen($file, "w");
        });
        if(! $handle) {
            throw new \RuntimeException("fopen failed: " . $phpErrorHandler->getErrorMsg('unknown error'));
        }
        $path = $this->getRealPath($file);
        $res = $phpErrorHandler->call(function()use($handle){
            return fclose($handle);
        });
        if(! $res) {
            throw new \RuntimeException("fclose failed: " . $phpErrorHandler->getErrorMsg('unknown error'));
        }
        return $path;
    }
    
    private function _deleteFile(string $file) {
        if(! is_file($file)) {
            throw new \RuntimeException("cannot delete file: file not found: {$file}");
        }
        $phpErrorHandler = new PhpErrorHandler();
        $res = $phpErrorHandler->call(function()use($file){
            return unlink($file);
        });
        if(! $res) {
            throw new \RuntimeException("unlink failed: " . $phpErrorHandler->getErrorMsg('unknown error'));
        }
        return true;
    }
    
    public function getRealPath(string $path) {
        $phpErrorHandler = new PhpErrorHandler();
        $res = $phpErrorHandler->call(function()use($path){
            return realpath($path);
        });
        if(false === $res) {
            throw new \RuntimeException("realpath failed: " . $phpErrorHandler->getErrorMsg('unknown error'));
        }
        return $res;
    }
    
    public function normalizeFilePath(string $path) {
        $osSep = DIRECTORY_SEPARATOR;
        $sep = ('/' === $osSep) ? '\\' : '/';
        $return = rtrim(str_replace($sep, $osSep, $path), $osSep);
        return $return;
    }
}
