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

    protected $_map = [    
        'pdf' => 'application/pdf',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'php' => 'application/x-httpd-php',
        'aac' => 'audio/aac',
        'abw' => 'application/x-abiword',
        'arc' => 'application/x-freearc',
        'avif' => 'image/avif',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'cda' => 'application/x-cdf',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxml',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'gz' => 'application/gzip',
        'htm' => 'text/html',
        'html'=> 'text/html',
        'ico' => 'image/vnd.microsoft.icon',
        'ics' => 'text/calendar',
        'jar' => 'application/java-archive',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'jsonld' => 'application/ld+json',
        'mid' => 'audio/midi audio/x-midi',
        'midi' => 'audio/midi audio/x-midi',
        'mjs' => 'text/javascript',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpkg' => 'application/vnd.apple.installer+xml',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'opus' => 'audio/opus',
        'otf' => 'font/otf',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxml',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'sh' => 'application/x-sh',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' =>'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'video/mp2t',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'vsd' => 'application/vnd.visio',
        'wav' => 'audio/wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxml',
        'xml' => 'application/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip',
        '3gp' => 'video/3gpp; audio/3gpp',
        '3g2' => 'video/3gpp2; audio/3gpp2',
        '7z' => 'application/x-7z-compressed',
    ];
    
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
            } catch (\Throwable $ex) {
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
                    // return mime type ala mimetype extension
                    $method = $isData ? 'finfo_buffer' : 'finfo_file';
                    $mime = $phpErrorHandler->call(function()use($finfo, $method, $fileOrData){return $method($finfo, $fileOrData);});
                    $phpErrorHandler->call(function()use($finfo){return finfo_close($finfo);});
                }
            }
            if(empty($mime)) {
                if(! $isData && function_exists('mime_content_type')) {
                    $mime = $phpErrorHandler->call(function()use($fileOrData){return mime_content_type($fileOrData);});
                }
                if(empty($mime)) {
                    $filename = empty($name) ? $fileOrData : $name;
                    $ext = strtolower(trim(pathinfo($filename, PATHINFO_EXTENSION)));
                    if(isset($this->_map[$ext])) {
                        return $this->_map[$ext];
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
     * Attempts to determine a file's MIME type.
     *
     * @param string  $extension  file extension
     *
     * @return string
     */
    public function getFileMimeTypeFromExtension(string $extension) {
        $ext = $extension;
        if(false !== strpos($ext, '.')) {
            $split = explode('.', $ext);
            if(count($split)) {
                $ext = array_pop($split);
            }
        }
        $ext = strtolower(trim($ext));
        return isset($this->_map[$ext]) ? $this->_map[$ext] : false;
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
