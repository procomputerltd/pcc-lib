<?php
namespace Procomputer\Pcclib\Http;

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

use Procomputer\Pcclib\Media\MediaConst;
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\FileSystem;
use Procomputer\Pcclib\Arrays;

class Upload {

    protected $_lastError = '';

    /**
     * Moves uploaded files to the specified directory path. This is usually preceded by a call to assembleUploadedFiles($_FILES)
     * @param string $destPath      Destination directory path.
     * @param array  $uploadedFiles Uploaded files derived from assembleUploadedFiles().
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
     * @param string $destFile       Destination file path.
     * @param array  $fileProperties Uploaded file properties.
     * @return bool
     */
    public function moveUploadedFile(string $destFile, array $fileProperties) {
        if(Types::isBlank($destFile)) {
            throw new \InvalidArgumentException("destPath parameter is empty of not a directory.");
        }
        $name = pathinfo($destFile, PATHINFO_BASENAME);
        $file = $fileProperties['tmp_name'] ?? '';
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
            $res = $this->_execPhp(function() use($file, $destFile) {
                return move_uploaded_file($file, $destFile);
            });
            if(false !== $res) {
                return true;
            }
            $msg = $this->_lastError;
            $this->_lastError = '';
            if(Types::isBLank($msg)) {
                $msg = "An unknown upload error ocurred";
            }
        }
        $this->_lastError = "Cannot copy uploaded file {$name}: {$msg}";
        return false;
    }

    /**
     * Assembles (normalizes) $_FILES superglobal array of uploaded files into
     * individual arrays each having these elements:
     *   name          (string) Chinese Balloon.jpg
     *   type          (string) image/jpeg
     *   size          (int)    21892
     *   tmp_name      (string) C:\Windows\Temp\phpDCE8.tmp
     *   error         (int)    0
     *   full_path     (string) Chinese Balloon.jpg
     *   errorMessage  (string) An error message describing 'error' number.
     *
     * @return array Returns the assembled files.
     */
    public function assembleUploadedFiles(array $files) {
        if(! count($files)) {
            return [];
        }
        $fileItems = [];
        foreach($files as $elmName => $fileData) {
            $fileList = [];
            foreach($fileData as $propName => $values) {
                if(! is_array($values)) {
                    $values = [$values];
                }
                $index = 0;
                foreach($values as $value) {
                    $fileList[$index++][$propName] = $value;
                }
            }
            $fileItems[$elmName] = $this->processUploadedFiles($fileList);
        }
        return $fileItems;
    }

    /**
     * Process and validate uploaded file data. Sets 'error' and 'errorMessage' properties.
     * @param array $files The $_FILES data.
     * @return array Returns the processed file data.
     */
    public function processUploadedFiles(array $files) {
        if(empty($files)) {
            return [];
        }
        $fileData = reset($files);
        if(! is_array($fileData) || empty($fileData)) {
            return [];
        }
        if(1 === count($files)) {
            $error = is_numeric($fileData['error'] ?? null) ? $fileData['error'] : 4;
            if(4 === $error) {
                return [];
            }
        }
        $return = [];
        $index = 0;
        foreach($files as $fileProperties) {
            /*  Samples of properties in each file download:
                name       (string) Haveaniceday.jpg
                tmp_name   (string) C:\Windows\Temp\phpC95.tmp
                type       (string) image/jpeg
                error      (int)    0
                size       (int)    4392948
                full_path  (string) Haveaniceday.jpg
            */
            $defaults = [
                'name' => '',
                'tmp_name' => '',
                'type' => '',
                'error' => 0,
                'size' => 0,
                'full_path' => '',
                'errorMessage' => ''
            ];
            $properties = Arrays::extend($defaults, $fileProperties, true, false, false);
            if(empty($properties['name'])) {
                $properties['name'] = 'downloaded_file_' . ++$index;
            }
            $error = $this->getUploadError($properties['error']);
            $errMsg = '';
            if(! $error) {
                $filename = $properties['tmp_name'];
                if(empty($filename)) {
                    $error = MediaConst::UPLOAD_ERR_TMP_NAME_MISSING;
                }
                elseif(! is_file($filename)) {
                    $error = MediaConst::UPLOAD_ERR_TMP_FILE_NOT_FOUND;
                }
                elseif(! is_readable($filename)) {
                    $error = MediaConst::UPLOAD_ERR_TMP_FILE_NOT_READABLE;
                }
                if($error) {
                    $var = Types::getVartype($filename ?? '');
                    $errMsg = $this->getUploadErrorMessage($error, 'upload error encountered');
                    if(false !== strpos($errMsg, '%s')) {
                        $errMsg = sprintf($errMsg, $var);
                    }
                }
            }
            if(0 !== $error && ! strlen($errMsg)) {
                $errMsg = $this->getUploadErrorMessage($error);
                if(! strlen($errMsg)) {
                    $errMsg = $this->getUploadErrorMessage(MediaConst::UPLOAD_ERR_INCOMPLETE); // The file download did not complete: an unknown error code was submitted.
                }
            }
            $properties['errorMessage'] = $errMsg;
            $return[] = new File($properties);
        }
        return $return;
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
        static $a = [
            // UPLOAD_ERR_OK Value 0 = no error
            MediaConst::UPLOAD_ERR_OK => 'The file is uploaded successfully.',
            // UPLOAD_ERR_INI_SIZE Value 1 = The uploaded file exceeds the upload_max_filesize directive in php.ini.
            MediaConst::UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the maximim file size.',
            // UPLOAD_ERR_FORM_SIZE Value 2 = The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
            MediaConst::UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the maximim file size.',
            // UPLOAD_ERR_PARTIAL Value 3 = The uploaded file was only partially uploaded.
            MediaConst::UPLOAD_ERR_PARTIAL => 'The file download did not complete: the file was only partially downloaded.',
            // UPLOAD_ERR_NO_FILE Value 4 = No file was uploaded. No file was selected using the file browse button.
            MediaConst::UPLOAD_ERR_NO_FILE => 'No file was selected using the file browse button.',
            // UPLOAD_ERR_NO_TMP_DIR Value 6 = Missing a temporary folder.
            MediaConst::UPLOAD_ERR_NO_TMP_DIR => 'The temporary file download folder is missing.',
            // UPLOAD_ERR_CANT_WRITE Value 7 = Failed to write file to disk.
            MediaConst::UPLOAD_ERR_CANT_WRITE => 'The file download did not complete: disk write failed.',
            // UPLOAD_ERR_EXTENSION 8 A PHP extension stopped the file upload. PHP does not provide a way to
            // ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.
            MediaConst::UPLOAD_ERR_EXTENSION => 'The file download stopped unexpectedly.',
            MediaConst::UPLOAD_ERR_INCOMPLETE => 'The file download did not complete: an unknown error code was submitted.',
            MediaConst::UPLOAD_ERR_TMP_NAME_MISSING => "'tmp_name' file path property is missing from the file download.",
            MediaConst::UPLOAD_ERR_TMP_FILE_NOT_FOUND => "file '%s' not found : file path does not exist",
            MediaConst::UPLOAD_ERR_TMP_FILE_NOT_READABLE => "file '%s' is not readable"
            ];
        return $a;
    }

    public function getLastError() {
        return $this->_lastError;
    }

    /**
     *
     * @param function $callback
     * @return mixed
     */
    protected function _execPhp($callback) {
        $errorObj = new \stdClass();
        $errorObj->fail = false;
        $errorObj->error = '';
        $errorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use($errorObj) {
            $errorObj->fail = true;
            $errorObj->error = $errstr;
        });
        try {
            $return = $callback();
        } catch (\Throwable $exc) {
            $errorObj->fail = true;
            $errorObj->error = $exc->getMessage();
            $return = false;
        }
        finally {
            set_error_handler($errorHandler);
        }
        $this->_lastError = $errorObj->error;
        return $errorObj->fail ? false : $return;
    }
}