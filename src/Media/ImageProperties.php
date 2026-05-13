<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR
A PARTICULAR PURPOSE. See the GNU General Public License
for more details.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Error;

/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer: Returns properties and information about an image file.
*/
class ImageProperties {

    /**
     * This class can be invoked as a function e.g. ImageProperties($file);
     *
     * @param string  $file  Path of file for which to get image properties.
     * @return array     Returns an array of image file properties (shown above).
     */
    public function __invoke(string $file) {
        return $this->getImageProperties($file);
    }

    /**
     * getImageProperties() returns an array of image information if the specified file is an image.
     * If specified file is not an image [errno] element is set to the error code.<pre>
     *  [filename]          = File path.
     * 	[file_ext]          = File extension without dot.
     * 	[width]             = Image pixel width.
     * 	[height]            = Image pixel height.
     * 	[type]              = A PHP 'IMAGETYPE_*' image type specifier.
     * 	[htmlSizeAttributes]= HTML <IMG%gt; tag string like 'width="1024" height="768"'
     * 	[mime]              = Mime-type like 'image/jp2' and 'image/png'
     * 	[channels]          = '3' for RGB pictures and '4' for CMYK pictures
     * 	[bits]              = The number of bits for each color.
     *  [errno]             = Error code number.
     *  [error]             = Error message.
     *  [throw]             = Indicates the error is a critical error that should be thrown.
     *  [info]              = Extra information extracted using getimagesize().</pre>
     *
     * @param string  $file  Path of file for which to get image properties.
     *
     * @return array     Returns an array of image file properties (shown above).
     * @throws InvalidFileException
     * @throws InvalidArgumentException
     */
    public function getImageProperties(string $file): array|bool {
        $fn = "getimagesize";
        if(! function_exists($fn)) {
            // cannot process images; the image function library is not available
            throw new Exception\NoGdLibraryException(MediaConst::T_NO_LIBRARY . " ({$fn})", MediaConst::E_NO_LIBRARY);
        }
        $sourcePath = $this->_resolveFile($file);
        if($sourcePath instanceof Error) {
            /** @var Error $sourcePath */
            switch($sourcePath->getState()) {
            case MediaConst::E_INVALID_FILE:
                throw new Exception\InvalidFileException($sourcePath->getMessage(), $sourcePath->getCode());
            default: // MediaConst::E_INVALID_ARGUMENT
                throw new Exception\InvalidArgumentException($sourcePath->getMessage(), $sourcePath->getCode());
            }
        }
        $phpErrorHandler = new PhpErrorHandler();
        $info = [];
        $properties = $phpErrorHandler->call(function()use($sourcePath, &$info){
            return getimagesize($sourcePath, $info);
        });
        if(is_array($properties) && isset($properties["mime"])) {
            /*  getimagesize() returns an array with these elements:

              [0] => 189                      // Width of the image in pixels.
              [1] => 591                      // Height of the image in pixels.
              [2] => 2                        // A PHP 'IMAGETYPE_*' image type specifier.
              [3] => width="189" height="591" // HTML <IMG> tag string like 'width="1024" height="768"'
              [mime] => image/jpeg            // Mime-type like 'image/jp2' and 'image/png'
              [channels] => 3                 // '3' for RGB pictures and '4' for CMYK pictures
              [bits] => 8                     // The number of bits for each color.

              'channels' applies to extended JPEG, JP2 files i.e. JPC, JP2, JPX, JB2, XBM, WBMP, SWC.

              See: http://us3.php.net/manual/en/function.getimagesize.php
              http://us3.php.net/manual/en/function.image-type-to-mime-type.php
             */
            $res["filename"]           = $sourcePath;
            $res["width"]              = isset($properties[0]) ? floatval($properties[0]) : 0.0;
            $res["height"]             = isset($properties[1]) ? floatval($properties[1]) : 0.0;
            $res["type"]               = isset($properties[2]) ? intval($properties[2]) : MediaConst::IMAGETYPE_UNKNOWN;
            $res["file_ext"]           = ImageType::getImageType($res["type"], true); // File extension without dot.
            $res["htmlSizeAttributes"] = isset($properties[3]) ? $properties[3] : "";
            $res["mime"]               = isset($properties["mime"]) ? $properties["mime"] : "";
            $res["channels"]           = isset($properties["channels"]) ? intval($properties["channels"]) : "";
            $res["bits"]               = isset($properties["bits"]) ? intval($properties["bits"]) : "";
            $res["errno"]              = 0;
            $res["error"]              = '';
            $res["throw"]              = false;
            $res['info']               = $info;
            return $res;
        }
        
        // the '%s' parameter is not image or is not a supported image format: '%s'
        $errorMsg = sprintf(MediaConst::T_NOT_IMAGE, 'file', Types::getVartype($sourcePath));
        if(! empty($phpErrorHandler->lastError)) {
            $errorMsg .= ': ' . $phpErrorHandler->getErrorMsg('getimagesize() function failed', 'cannot get file image information');
        }
        throw new Exception\InvalidFileException($errorMsg, MediaConst::E_NOT_IMAGE);
    }

    /**
     * 
     * @param string $file File
     * @param string $name (optional) Name of the file. If not specified $file is used.
     * @return string|Error
     */
    protected function _resolveFile(string $file) {
        if(Types::isBlank($file)) {
            // invalid source file parameter
            $code = MediaConst::E_BAD_SOURCE_FILE_PARAM;
            $state = MediaConst::E_INVALID_ARGUMENT;
            // invalid '%s' parameter '%s'
            $errorMsg = sprintf(MediaConst::T_PARAMETER_INVALID, 'file', Types::getVartype($file)) . ': expecting an image file';
        }
        else {
            if(! file_exists($file) || ! is_file($file)) {
                // file not found
                $code = MediaConst::E_FILE_NOT_FOUND;
                // file not found '%s'
                $errorMsg = sprintf(MediaConst::T_FILE_NOT_FOUND, Types::getVartype($file));
            }
            else {
                $fileSize = filesize($file);
                if($fileSize) {
                    return $file;
                }
                // the file is empty
                $code = MediaConst::E_FILE_EMPTY;
                // file '%s' is empty
                $errorMsg = sprintf(MediaConst::T_FILE_EMPTY, Types::getVartype(empty($name) ? $file : $name));
            }
        }
        return new Error($errorMsg, $code, $state ?? MediaConst::E_INVALID_FILE);
    }
    
    public function isInterlaced(string $file) {
        if(! file_exists($file) || ! is_file($file) || filesize($file) < 32) {
            return false;
        }
        $phpErrorHandler = new PhpErrorHandler();
        $contents = $phpErrorHandler->call(function()use($file){
            return file_get_contents($file, false, null, 0, 32);
        });
        if(false === $contents) {
            return false;
        }
        $hex = [];
        for($i=0; $i<strlen($contents); $i++) {
            // Interlaced       ff d8 ff e0 00 10 4a 46 49 46 00 01 01 01 00 60 00 60 00 00 ff fe 00 3c 43 52 45 41 54 4f 52 3a
            // Non-interlaced   ff d8 ff e0 00 10 4a 46 49 46 00 01 01 01 00 60 00 60 00 00 ff fe 00 3c 43 52 45 41 54 4f 52 3a
            $hex[] = str_pad(dechex(ord($contents[$i])), 2, '0', STR_PAD_LEFT);
        }
        $hex = implode(' ', $hex);
        $c = $contents[28];
        $o = ord($c);
        return $o;
    }
}
