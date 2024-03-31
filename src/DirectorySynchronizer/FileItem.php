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

use Procomputer\Pcclib\PhpErrorHandler;

class FileItem {
    
    /**
     * 
     * @var string
     */
    public $filepath = '';
    
    /**
     * 
     * @var string
     */
    public $subDir = '';
    
    /**
     * 
     * @var string
     */
    public $copyToDirectory = null;
    
    /**
     * 
     * @var int
     */
    public $key = 0;
    
    /**
     * 
     * @var FileItem
     */
    public $associateItem = null;
    
    /**
     * 
     * @var string
     */
    public $lastError = '';
    
    /**
     * 
     * @param string $filepath Item file path.
     * @param string $subDir   Item sub directory.
     * @param int    $key      Item primary or secondary key: 0 or 1
     */
    public function __construct(string $filepath, string $subDir, int $key) {
        $this->filepath = $filepath;
        $this->subDir = $subDir;
        $this->key = $key;
    }

    /**
     * 
     * @return bool
     */
    public function synchronize() {
        $phpErrorHandler = new PhpErrorHandler();
        /** @var FileItem $from */
        /** @var FileItem $to */
        if($this->associateItem) {
            if($this->getTimestamp() > $this->associateItem->getTimestamp()) {
                $from = $this->filepath;
                $to = $this->associateItem->filepath;
            }
            else {
                $from = $this->associateItem->filepath;
                $to = $this->filepath;
            }
        }
        elseif(! $this->copyToDirectory) {
            $this->lastError = "cannot synchronize: both \$this->associateItem and \$this->copyToDirectory are empty!";
            return false;
        }
        else {
            $from = $this->filepath;
            $to = $this->copyToDirectory . $this->subDir;
        }
        $res = $phpErrorHandler->call(function()use($from, $to){
            $dir = dirname($to);
            if(! is_dir($dir)) {
                $base = [];
                while(1) {
                    $base[] = basename($dir);
                    $dir = dirname($dir);
                    if(is_dir($dir)) {
                        break;
                    }
                }
                foreach(array_reverse($base) as $p) {
                    $dir .= DIRECTORY_SEPARATOR . $p;
                    $res = mkdir($dir);
                    if(! $res) {
                        return $res;
                    }
                }
            }
            return copy($from, $to);
        });
        if(false === $res) {
            $this->lastError = "file copy failed: " . $phpErrorHandler->getErrorMsg('unknown error');
            return false;
        }
        if(md5_file($from) !== md5_file($to)) {
            $this->lastError = "file copy failed. The source file hash does not match the target hash for file '{$from->subDir}'";
            return false;
        }
        return true;
    }
    
    public function associateItem(FileItem $fileItem) {
        $this->associateItem = $fileItem;
        return $this;
    }
    
    public function setCopyToDirectory(string $directory) {
        $this->copyToDirectory = $directory;
        return $this;
    }
    
    public function filenameHash() {
        return md5($this->subDir);
    }
    
    public function fileHash() {
        return hash_file('md5', $this->filepath);
    }

    /**
     * 
     * @return int
     */
    public function getTimestamp() {
        return intval(filemtime($this->filepath));
    }
    
    /**
     * Returns true if $this->filepath is readable.
     * @return bool
     */
    public function isReadable() {
        return (strlen($this->filepath) && is_file($this->filepath)) ? is_readable($this->filepath) : false;
    }
    
    /**
     * Returns true if $this->filepath is writeable.
     * @return bool
     */
    public function isWriteable() {
        return (strlen($this->filepath) && is_file($this->filepath)) ? is_writeable($this->filepath) : false;
    }
    
    /**
     * Returns true when the files are different.
     * @param FileItem $fileItem
     * @return bool
     */
    public function diff(FileItem $fileItem) {
        return $this->fileHash() !==  $fileItem->fileHash();
    }
    
    /**
     * 
     * @param FileItem $fileItem
     * @return \DateInterval
     */
    public function diffTime(FileItem $fileItem) {
        $t1 = $this->getTimestamp();
        $t2 = $fileItem->getTimestamp();
        return $t1 - $t2;
    }
    
    /**
     * 
     * @return \DateTime
     */
    public function getDateTime() {
        $time = new DateTime();
        $time->setTimestamp($this->getTimestamp());
        return $time;
    }
    
    public function __toString(): string {
        return $this->filepath;
    }
}
