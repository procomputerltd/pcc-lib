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
     * @param boolean $throw (optional) Throw an exception when getimagesize() function fails, otherwise
     *                                  return the GD or other error in the 'error' key of the returned array.
     *
     * @return array     Returns an array of image file properties (shown above).
     */
    public function __invoke(string $file, bool $throw = true) {
        return $this->getImageProperties($file, $throw);
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
     * @param boolean $throw (optional) Throw an exception when getimagesize() function fails, otherwise
     *                                  return the GD or other error in the 'error' key of the returned array.
     *
     * @return array     Returns an array of image file properties (shown above).
     *
     */
    public function getImageProperties(string $file, bool $throw = true) {
        $sourcePath = $errorMsg = "";
        $properties = [];
        $info = [];
        $shouldThrow = true; // Throw an error by default. If FALSE the error is included in the returned property array.
        $code = 0;
        if(! function_exists("getimagesize")) {
            // cannot process images; the image function library is not available
            $code = MediaConst::E_NO_LIBRARY;
            $errorMsg = MediaConst::T_NO_LIBRARY;
        }
        elseif(Types::isBlank($file)) {
            // invalid source file parameter
            $code = MediaConst::E_BAD_SOURCE_FILE_PARAM;
            // invalid '%s' parameter '%s'
            $errorMsg = sprintf(MediaConst::T_PARAMETER_INVALID, 'file', Types::getVartype($file)) . ': expecting an image file';
        }
        else {
            $sourcePath = trim($file);
            if(! file_exists($sourcePath) || ! is_file($sourcePath)) {
                // file not found
                $code = MediaConst::E_FILE_NOT_FOUND;
                // file not found '%s'
                $errorMsg = sprintf(MediaConst::T_FILE_NOT_FOUND, Types::getVartype($file));
            }
            else {
                $fileSize = filesize($sourcePath);
                if(! $fileSize) {
                    // the file is empty
                    $code = MediaConst::E_FILE_EMPTY;
                    // file '%s' is empty
                    $errorMsg = sprintf(MediaConst::T_FILE_EMPTY, Types::getVartype($sourcePath));
                }
                else {
                    // Don't throw an error; the error is included in the resturned property array.
                    $shouldThrow = false;
                    $phpErrorHandler = new PhpErrorHandler();
                    $properties = $phpErrorHandler->call(function()use($sourcePath, &$info){
                        return getimagesize($sourcePath, $info);
                    });
                    if(!is_array($properties) || !isset($properties["mime"])) {
                        $code = MediaConst::E_NOT_IMAGE;
                        // the '%s' parameter is not image or is not a supported image format: '%s'
                        $errorMsg = sprintf(MediaConst::T_NOT_IMAGE, 'file', Types::getVartype($sourcePath));
                        if(! empty($phpErrorHandler->lastError)) {
                            // the '%s' parameter is not image or is not a supported image format: '%s'
                            $errorMsg .= ': ' . $phpErrorHandler->getErrorMsg('getimagesize() function failed', 'cannot get file image information');
                        }
                    }
//                    else {
//                        $interlace = $this->isInterlaced($sourcePath);
//                    }
                }
            }
        }
        if($code && $throw) {
            throw new Exception\InvalidArgumentException($errorMsg, $code);
        }
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
        $res["errno"]              = $code;
        $res["error"]              = $errorMsg;
        $res["throw"]              = $shouldThrow;
        $res['info']               = $info;
        return $res;
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
