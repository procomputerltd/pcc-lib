<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : CsvFile helper functions
*/
namespace Procomputer\Pcclib;

use Procomputer\Pcclib\FileSystem,
    Procomputer\Pcclib\Types;

/**
 * Read and writes CSV files.
 */
class CsvFile extends Common {

    /**
     * Switch: throw errors or return an Error object when a major error occurrs.
     * @var boolean
     */
    private static $_throwErrors = true;
    
    /**
     * Sets the throw errors setting that determines whether an exception is thrown on severe 
     * errors or an Error object is returned on severe errors.
     * @param boolean $throw (optional) Sets the throw errors setting. If null the setting is not changed.
     * @return boolean Returns the previous throw errors setting.
     */
    public static function throwErrors($throw = null) {
        $return = self::$_throwErrors;
        if(null !== $throw) {
            self::$_throwErrors = (bool)$throw;
        }
        return $return;
    }
    
    /**
     * Writes data to a CSV file.
     * @param string             $file      Basename or full path of file to write.
     * @param array|\Traversable $data      Data to be written to the file.
     * @param function           $callBack  (optional) Function called before each fputcsv() call. prototype function($dataRow, $fileHandle, $lineCount)
     * @param string             $delimiter (optional) CSV delimiter character.
     * @param string             $enclosure (optional) CSV enclosure character.
     * @param string             $escape    (optional) CSV escape character. 
     * @return string|boolean Returns the file path else FALSE on error.
     */
    public function write($file, $data, $callBack = null, $delimiter = ",", $enclosure = '"', $escape = "\\") {
        $handle = $this->_open($file, false);
        if(is_array($handle)) {
            list($msg, $code) = $handle;
            if(self::$_throwErrors) {
                throw new Exception\RuntimeException($msg, $code);
            }
            return new Error($msg, $code);
        }
        $lineCount = 0;
        $callable = is_callable($callBack);
        $phpErrorHandler = new PhpErrorHandler();
        foreach($data as $row) {
            $res = $phpErrorHandler->call(function()use($handle, $row, $callable, $callBack, $lineCount, $delimiter, $enclosure, $escape){
                if($callable) {
                    $rowArray = $callBack($row, $handle, $lineCount);
                    if(! is_array($rowArray)) {
                        $rowArray = (array)$rowArray;
                    }
                }
                else {
                    $rowArray = is_array($row) ? $row : (array)$row;
                }
                return fputcsv($handle, $rowArray, $delimiter, $enclosure, $escape);
            });
            if(! $res) {
                $msg = $phpErrorHandler->getErrorMsg('cannot write CSV file', 'fputcsv() failed');
                fclose($handle);
                if(self::$_throwErrors) {
                    throw new Exception\RuntimeException($msg, Constant::E_FILE_WRITE);
                }
                return new Error($msg, Constant::E_FILE_WRITE);
            }
            $lineCount++;
        }
        $phpErrorHandler->call(function()use($handle){return fclose($handle);});
        return $lineCount;
    }
    
    /**
     * Reads a CSV file into an array.
     * @param string             $file      File from which to read CSV data.
     * @param string             $delimiter (optional) CSV delimiter character.
     * @param string             $enclosure (optional) CSV enclosure character.
     * @param string             $escape    (optional) CSV escape character. 
     * @return array|boolean Returns the CSV data array else FALSE on error.
     */
    public function read($file, $delimiter = ",", $enclosure = '"', $escape = "\\") {
        $handle = $this->_open($file, true);
        if(is_array($handle)) {
            list($msg, $code) = $handle;
            if(self::$_throwErrors) {
                throw new Exception\RuntimeException($msg, $code);
            }
            return new Error($msg, $code);
        }
        $data = [];
        $phpErrorHandler = new PhpErrorHandler();
        while(1) {
            if(feof($handle)) {
                break;
            }
            $line = $phpErrorHandler->call(function()use($handle, $delimiter, $enclosure, $escape){
                return fgetcsv($handle, 99999, $delimiter, $enclosure, $escape);                
            });
            if(! $line) {
                $msg = $phpErrorHandler->getErrorMsg('', '');
                if(! empty($msg)) {
                    if(self::$_throwErrors) {
                        throw new Exception\RuntimeException($msg, Constant::E_FILE_READ);
                    }
                    return new Error($msg, Constant::E_FILE_READ);
                }
                break;
            }
            $data[] = $line;
        }
        $phpErrorHandler->call(function()use($handle){return fclose($handle);});
        return $data;
    }
    
    /**
     * Opens a file for read or write.
     * @param string  $file File to open.
     * @param boolean $read When TRUE open for read else write.
     * @return resource|array Returns the opened resource handle or array [error_msg, error_code] on error.
     */
    protected function _open($file, $read = false) {
        if(! is_string($file) || Types::isBlank($file)) {
            $msg = "not a valid file path";
            $code = Constant::E_PARAMETER_INVALID;
        }
        else {
            $path = trim($file);
            $dir = dirname($path);
            if(! strlen($dir) || '.' === $dir) {
                $path = Filesystem::joinPath(DIRECTORY_SEPARATOR, getcwd(), $path);
            }
            else {
                $path = Filesystem::replaceWithOsSlashes($path);
            }
            if($read) {
                $realpath = FileSystem::getRealPath($path);
                if(false === $realpath) {
                    if(! file_exists($path) || ! is_file($path)) {
                        $msg = "file not found";
                        $code = Constant::E_FILE_NOT_FOUND;
                    } 
                    else {
                        $msg = "not a valid file path";
                    }
                }
                else {
                    $path = $realpath;
                    if(! file_exists($path) || ! is_file($path)) {
                        $msg = "file not found";
                        $code = Constant::E_FILE_NOT_FOUND;
                    }
                    elseif(! is_readable($path) || ! filesize($path)) {
                        $msg = "file is not readable or is empty";
                    }
                    else {
                        $mode = 'r';
                    }
                }
            }
            else {
                $mode = 'wb';
                if(file_exists($path)) {
                    $path = FileSystem::getRealPath($path);
                    if(false === $path || ! is_file($path)) {
                        $msg = "not a file";
                    }
                    elseif(!is_writeable($path)) {
                        $msg = "file is not writeable";
                    }
                }
            }
        }
        if(isset($msg)) {
            if(! isset($code)) {
                $code = Constant::E_FILE_OPEN;
            }
            $var = Types::getVartype($file);
            $msg = "cannot open file: {$msg}: {$var}";
            return [$msg, $code];
        }
        
        $phpErrorHandler = new PhpErrorHandler();
        $handle = $phpErrorHandler->call(function()use($path, $mode){return fopen($path, $mode);});
        if(! $handle) {
            $msg = $phpErrorHandler->getErrorMsg('cannot open CSV file', 'fopen() failed');
            return [$msg, Constant::E_FILE_OPEN];
        }
        return $handle;
    }
}
