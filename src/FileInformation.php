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
    Description : Gets information about a file.
*/
namespace Procomputer\Pcclib;

/**
 * Gets information about a file.
 */
class FileInformation {

    /**
     * Attempts to determine a file's MIME type.
     *
     * @param string  $fileOrData File or data for which to fetch MIME type.
     * @param boolean $isData     $fileOrData is data, not a file.
     *
     * @return boolean|string
     */
    public function getFileMimeType($fileOrData, $isData = false) {
        if(class_exists('\finfo', false)) {
            try {
                $info = new \finfo();
                if($isData) {
                    $mime = $info->buffer($fileOrData, FILEINFO_MIME);
                }
                else {
                    $mime = $info->file($fileOrData, FILEINFO_MIME);
                }
            } catch (Exception $ex) {
                $mime = false;
            }
        }
        else {
            $mime = false;
        }

        $phpErrorHandler = new PhpErrorHandler();
        
        if(empty($mime)) {
            if(function_exists('finfo_open')) {
                
                $finfo = $phpErrorHandler->call(function(){
                    return finfo_open(FILEINFO_MIME_TYPE);
                });
                if($finfo) {
                    ; // return mime type ala mimetype extension
                    $method = $isData ? 'finfo_buffer' : 'finfo_file';
                    $mime = $phpErrorHandler->call(function()use($finfo, $method, $fileOrData){return $method($finfo, $fileOrData);});
                    $phpErrorHandler->call(function()use($finfo){return finfo_close($finfo);});
                }
            }
            if(empty($mime)) {
                if(! $isData && function_exists('mime_content_type')) {
                    $mime = $phpErrorHandler->call(function()use($fileOrData){return mime_content_type($fileOrData);});;
                }
                if(empty($mime)) {
                    $filename = empty($name) ? $fileOrData : $name;
                    $ext = strtolower(trim(pathinfo($filename, PATHINFO_EXTENSION)));
                    switch($ext) {
                    case 'jpeg':
                    case 'jpg':
                        return 'image/jpg';
                    case 'png':
                        return 'image/png';
                    case 'gif':
                        return 'image/gif';
                    case 'zip':
                    case 'zipx':
                    case '7z':
                    case 'tar':
                    case 'rar':
                        return 'application/zip';
                    case 'gz':
                    case 'gzip':
                        return 'application/gzip';
                    case 'txt':
                    case 'php':
                    case 'phtml':
                        return 'text/plain';
                    case 'htm':
                    case 'html':
                        return 'text/html';
                    case 'xml':
                        return 'text/xml';
                    }
                    return false;
                }
            }
        }
        $offset = strpos($mime, ';', 0);
        if(false !== $offset) {
            $mime = trim(substr($mime, 0, $offset));
        }
        return $mime;
    }

    /**
     * Returns a description for the specified file extension.
     * @param string $fileExtension The file extension for which to find description.
     * @return string
     */
    public function getFileExtensionDescription($fileExtension) {
        if(Types::isBlank($fileExtension)) {
            return null;
        }
        $data = $this->getFileExtensionTable();
        if(! is_array($data)) {
            return null;
        }
        $flipped = array_flip($data);
        $lowerExt = strtolower($fileExtension);
        if(! isset($flipped[$lowerExt])) {
            return null;
        }
        $pointers = $this->getFileExtensionDescriptionOffsetTable();
        if(! is_array($pointers)) {
            return null;
        }
        $index = $flipped[$lowerExt];
        if(! isset($pointers[$index])) {
            return null;
        }
        list($offset, $len) = array_map('intval', explode(',', $pointers[$index]));
        $descripFile = dirname(__FILE__) . '/file_ext_desc.txt';
        if(! is_file($descripFile)) {
            return null;
        }
        $phpErrorHandler = new PhpErrorHandler();
        $handle = $phpErrorHandler->call(function()use($descripFile){return fopen($descripFile, "rb");});
        if(false === $handle) {
            return null;
        }
        // Set the file pointer to a byte offset of 138 to begin reading
        if(-1 === fseek($handle, $offset)) {
            fclose($handle);
            return null;
        }
        $description = fread($handle, $len);
        fclose($handle);
        return (false === $description) ? null : $description;
    }

    /**
     * Returns array file extensions and their descriptions.
     * @param boolean $associate  Return an associated array extension=>description.
     * @return array|boolean
     */
    public function getFileExtensionTable() {
        $file = dirname(__FILE__) . '/file_ext.txt';
        return $this->_fileToArray($file);
    }

    /**
     * Returns array file extensions and their descriptions.
     * @param boolean $associate  Return an associated array extension=>description.
     * @return array|boolean
     */
    public function getFileExtensionDescriptionOffsetTable() {
        $file = dirname(__FILE__) . '/file_ext_desc_offset.csv';
        return $this->_fileToArray($file);
    }

    /**
     * Reads a text file into an array.
     * @return array|boolean
     */
    protected function _fileToArray($file, $options = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) {
        if(is_file($file)) {
            $phpErrorHandler = new PhpErrorHandler();
            $data = $phpErrorHandler->call(function()use($file, $options){return file($file, $options);});
            if(false !== $data) {
                return $data;
            }
        }
        return false;
    }
}
