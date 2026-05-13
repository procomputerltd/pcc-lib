<?php
/*
 * Copyright (C) 2023 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 */
namespace Procomputer\Pcclib\Http;

use Procomputer\Pcclib\Http\File as HttpFile;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Media\MediaConst;
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\FileSystem;
use Procomputer\Pcclib\Exception\InvalidArgumentException;

class Upload {

    public $uploadErrors = [
        // UPLOAD_ERR_OK Value 0 = no error
        MediaConst::UPLOAD_ERR_OK => 'The file is uploaded successfully.',
        // UPLOAD_ERR_INI_SIZE Value 1 = The uploaded file exceeds the upload_max_filesize directive in php.ini.
        MediaConst::UPLOAD_ERR_INI_SIZE => '%s: the uploaded file exceeds the maximim file size.',
        // UPLOAD_ERR_FORM_SIZE Value 2 = The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
        MediaConst::UPLOAD_ERR_FORM_SIZE => '%s: the uploaded file exceeds the maximim file size.',
        // UPLOAD_ERR_PARTIAL Value 3 = The uploaded file was only partially uploaded.
        MediaConst::UPLOAD_ERR_PARTIAL => '%s: the file download did not complete: the file was only partially downloaded.',
        // UPLOAD_ERR_NO_FILE Value 4 = No file was uploaded. No file was selected using the file browse button.
        MediaConst::UPLOAD_ERR_NO_FILE => 'No file was selected using the file browse button.',
        // UPLOAD_ERR_NO_TMP_DIR Value 6 = Missing a temporary folder.
        MediaConst::UPLOAD_ERR_NO_TMP_DIR => 'The temporary file download folder is missing.',
        // UPLOAD_ERR_CANT_WRITE Value 7 = Failed to write file to disk.
        MediaConst::UPLOAD_ERR_CANT_WRITE => 'The file download did not complete: disk write failed.',
        // UPLOAD_ERR_EXTENSION 8 A PHP extension stopped the file upload. PHP does not provide a way to
        // ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.
        MediaConst::UPLOAD_ERR_EXTENSION => '%s: the file download stopped unexpectedly.',
        MediaConst::UPLOAD_ERR_INCOMPLETE => 'The file download did not complete: an unknown error code was submitted.',
        MediaConst::UPLOAD_ERR_TMP_NAME_MISSING => "'tmp_name' file path property is missing from the file download.",
        MediaConst::UPLOAD_ERR_TMP_FILE_NOT_FOUND => "file '%s' not found : file path does not exist",
        MediaConst::UPLOAD_ERR_TMP_FILE_NOT_READABLE => "file '%s' is not readable"
    ];

    protected $_files = [];

    protected $_lastError = '';

    /**
     *
     * @param array|HttpFile $files   Uploaded files to import.
     * @param array          $options (optional) Options.
     * @return void
     */
    public function __construct(array|HttpFile $files = null, array $options = []) {
        if(null !== $files) {
            $this->importUploadedFiles($files, $options);
        }
    }

    /**
     * Process and validate uploaded file data. Sets 'error' and 'errorMessage' properties.
     * @param array|HttpFile $files   Uploaded files to import.
     * @param array          $options (optional) Options.
     * @return array Returns the processed file data.
     */
    public function importUploadedFiles(array|HttpFile $files, array $options = []) {
        $uploadLists = $this->_resolveFiles($files, $options);
        $this->_files = [];
        if(empty($uploadLists)) {
            return [];
        }
        $return = [];
        foreach($uploadLists as $uploadList) {
            foreach($uploadList as $fileObject) {
                /** @var \Procomputer\Pcclib\Http\File $fileObject */
                $error = $fileObject->getError();
                if(MediaConst::UPLOAD_ERR_NO_FILE !== $error) {
                    $error = $this->getUploadError($error);
                    $errMsg = '';
                    $filename = $fileObject->getName();
                    if(! $error) {
                        $tempName = $fileObject->getTmpName();
                        if(empty($tempName)) {
                            $error = MediaConst::UPLOAD_ERR_TMP_NAME_MISSING;
                        }
                        elseif(! is_file($tempName)) {
                            $error = MediaConst::UPLOAD_ERR_TMP_FILE_NOT_FOUND;
                        }
                        elseif(! is_readable($tempName)) {
                            $error = MediaConst::UPLOAD_ERR_TMP_FILE_NOT_READABLE;
                        }
                        if($error) {
                            $errMsg = $this->getUploadErrorMessage($error, 'upload error encountered');
                            if(false !== strpos($errMsg, '%s')) {
                                $errMsg = sprintf($errMsg, $filename);
                            }
                        }
                    }
                    if(0 !== $error && ! strlen($errMsg)) {
                        $errMsg = $this->getUploadErrorMessage($error);
                        if(! strlen($errMsg)) {
                            $errMsg = $this->getUploadErrorMessage(MediaConst::UPLOAD_ERR_INCOMPLETE); // The file download did not complete: an unknown error code was submitted.
                        }
                        elseif(false !== strpos($errMsg, '%s')) {
                            $errMsg = sprintf($errMsg, $filename);
                        }
                    }
                    $fileObject->setErrorMessage($errMsg);
                    $return[] = $fileObject;
                }
            }
        }
        $this->_files = $return;
        return $return;
    }

    /**
     *
     * @param array|HttpFile $files
     * @param array          $options (optional) Options.
     * @return array
     */
    private function _resolveFiles(array|HttpFile $files, array $options = []) {
        if($files instanceof HttpFile) {
            return [$files->getName() => [$files]];
        }
        if(! count($files)) {
            return [];
        }
        $uploadLists = ($options['raw'] ?? false) 
            ? $this->_convertRawFiles($files) // 'raw' indicates the 'files' parameter is straight from $_FILES i.e. raw.
            : $this->_assembleFiles($files);
        foreach($uploadLists as $key => $uploadList) {
            foreach($uploadList as $listKey => $properties) {
                if($properties instanceof HttpFile) {
                    $uploadList[$listKey] = $properties;
                }
                else {
                    $res = $this->_validPropNames($properties);
                    if(is_string($res)) {
                        $msg = "the parameter that specified the uploaded file data contains invalid property name(s): '{$res}'";
                        throw new InvalidArgumentException($msg);
                    }
                    $uploadList[$listKey] = new HttpFile($properties);
                }
            }
            $uploadLists[$key] = $uploadList;
        }
        return $uploadLists;
    }

    /**
     * 
     * @param array $files
     * @return array
     */
    private function _assembleFiles(array $files) {
        $return = [];
        foreach($files as $key => $fileData) {
            if(is_array($fileData)) {
                foreach($fileData as $k => $properties) {
                    if(is_array($properties)) {
                        $res = $this->_validPropNames($properties);
                        if(is_string($res)) {
                            $msg = "the parameter that specified the uploaded file data contains invalid property name(s): '{$res}'";
                            throw new InvalidArgumentException($msg);
                        }
                    }
                    else {
                        if(! $fileData instanceof HttpFile) {
                            $var = Types::getVartype($fileData);
                            $msg = "the parameter that specifies the uploaded file data is invalid: expecting non-empty array or File object, got '{$var}'";
                            throw new InvalidArgumentException($msg);
                        }
                    }
                }
            }
        }
        return $return;
    }
    
    /**
     * 
     * @param array $files
     * @return array
     */
    private function _convertRawFiles(array $files) {
        $return = [];
        foreach($files as $elmName => $fileData) {
            $list = [];
            foreach($fileData as $propName => $values) {
                if(! is_array($values)) {
                    $values = [$values];
                }
                $index = 0;
                foreach($values as $value) {
                    $list[$index++][$propName] = $value;
                }
            }
            if(count($list)) {
                $return[$elmName] = $list;
            }
        }
        return $return;
    }

    /**
     * 
     * @param array $properties
     * @return string|bool
     */
    private function _validPropNames(array $properties) {
        $badNames = [];
        foreach($properties as $propName => $value) {
            /*
            [name]      => (string) nodejs-new-pantone-black.svg
            [type]      => (string) image/svg+xml
            [size]      => (int) 15241
            [tmp_name]  => (string) C:\Windows\Temp\php4BD7.tmp
            [error]     => (int) 0
            [full_path] => (string) nodejs-new-pantone-black.svg
            */
            if(! $this->_validPropName($propName)) {
                $badNames[] = $propName;
            }
        }
        return count($badNames) ? implode(', ', $badNames) : true;
    }
    
    private function _validPropName($propName) {
        return in_array($propName, ['name', 'type', 'size', 'tmp_name', 'error', 'full_path']);
    }

    /**
     * Returns an array of files
     * @return array
     */
    public function getFiles(): array {
        return $this->_files;
    }

    /**
     * Moves uploaded files to the specified directory path. This is usually preceded by a call to importUploadedFiles($_FILES)
     * @param string $destPath      Destination directory path.
     * @param array  $uploadedFiles Uploaded files derived from importUploadedFiles().
     * @return type
     */
    public function moveUploadedFiles(string $destPath, array $uploadedFiles) {
        if(Types::isBlank($destPath) || ! is_dir($destPath)) {
            throw new \InvalidArgumentException("destPath parameter is empty or not a directory.");
        }
        $index = 1;
        foreach($uploadedFiles as $elmName => $items) {
            foreach($items as $key => $properties) {
                $errors = [];
                $name = $properties['name'] ?? null;
                if(! is_string($name) || ! strlen($name = trim($name))) {
                    $name = 'File_upload_' . $index;
                }
                $error = $properties['error'] ?? 0;
                if(is_numeric($error) && intval($error)) {
                    $errors[] =  "Cannot copy uploaded file {$name}: " . (Types::isBlank($properties['errorMessage'] ?? '')
                        ? "an unknown uploaded file error ocurred." : $properties['errorMessage']);
                }
                else {
                    $destFile = FileSystem::joinPath(DIRECTORY_SEPARATOR, $destPath, $name);
                    $properties = $this->moveUploadedFile($destFile, $properties);
                }
                $items[$key] = $properties;
                $index++;
            }
            $uploadedFiles[$elmName] = $items;
        }
        return $uploadedFiles;
    }

    /**
     * Moves uploaded file to the specified directory path.
     * @param string $destFile    Destination file path.
     * @param File   $fileObject  Uploaded File object.
     * @return bool
     */
    public function moveUploadedFile(string $destFile, File $fileObject) {
        if(Types::isBlank($destFile)) {
            throw new \InvalidArgumentException("destPath parameter is empty of not a directory.");
        }
        $name = pathinfo($destFile, PATHINFO_BASENAME);
        $file = $fileObject->getTmpName() ?? '';
        $msg = false;
        if(Types::isBLank($file)) {
            $msg = "missing.";
        }
        elseif(! is_file($file)) {
            $msg = "missing or not a file.";
        }
        elseif(! is_readable($file)) {
            $msg = "not readable.";
        }
        if($msg) {
            $msg = "The 'tmp_name' temporary filename is {$msg}";
        }
        elseif(! is_uploaded_file($file)) {
            $msg = "The file is not an uploaded file.";
        }
        else {
            $phpErrorHandler = new PhpErrorHandler();
            $res = $phpErrorHandler->call(function()use($file, $destFile){
                return move_uploaded_file($file, $destFile);
            });
            if(false !== $res) {
                return true;
            }
            $msg = $phpErrorHandler->getErrorMsg("move_uploaded_file() failed");
        }
        $this->_lastError = "Cannot copy uploaded file {$name}: {$msg}";
        return false;
    }

    /**
     * Resolves an upload error number.
     * @param int   $errno   Error number to resolve.
     * @param mixed $default Value returned when error is invalid.
     * @return string
     */
    public function getUploadError($errno, mixed $default = null) {
        if(! is_numeric($errno)) {
            return $default;
        }
        $e = intval($errno);
        if(! $e) {
            return 0;
        }
        return ($e < 0) ? $default : $e;
    }

    /**
     * Returns an upload error message for the specified error number. Return $default if not found.
     * @param int   int|float|string Error number for which to return message.
     * @param mixed $default Value returned when no error message is found.
     * @return string
     */
    public function getUploadErrorMessage(int|float|string $errno, mixed $default = '') {
        if(! is_numeric($errno)) {
            return $default;
        }
        $e = intval($errno);
        if(! $e) {
            return $default;
        }
        $a = $this->getUploadErrorList();
        return $a[$e] ?? $default;
    }

    /**
     * Returns a file upload error_number => message list.
     * @param function $callback
     * @return mixed
     */
    public function getUploadErrorList() {
        return $this->uploadErrors;
    }

    public function getLastError() {
        return $this->_lastError;
    }
}