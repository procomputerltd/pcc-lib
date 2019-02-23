<?php

/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Dec 9, 2018, 8:46:02 AM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer 
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\FileSystem;

/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer: Common methods used by media/image classes.
*/
class ImportImage {

    public $lastErrorMsg = '';
    public $lastErrorCode = 0;

    /**
     * Void constructor
     */
    public function __construct() {
    }
    
    /**
     * Saves an image resource to a file.
     *
     * @param resource $imgResource  Image resource created by imagecreatetruecolor() or imagecreate()
     * @param string   $destFile     The file path to accept the image.
     * @param int      $phpType      Type of image to create. A PHP 'IMAGETYPE_*' image type specifier.
     * @param int      $quality      JPEG quality of the image. Default is 75. 0 = worst quality,
     *                               smaller file. 100 = best quality, biggest file.
     * @param boolean  $interlace    (optional) Apply interlacing to image.
     * @param boolean  $throw        (optional) When TRUE, throw an exception(default) when a function fails else return FALSE.
     *
     * @return mixed Returns the file path name to which the image is written or FALSE on error.
     */
    public function __invoke($imgResource, $destFile, $phpType, $quality, $interlace = 0, $throw = true) {
        return $this->import($imgResource, $destFile, $phpType, $quality, $interlace, $throw);
    }
    
    /**
     * Creates an image resource from a file.
     *
     * @param string  $file     File path from which to create image.
     * @param int     $phpType  (optional) PHP 'IMAGETYPE_*' constant.
     * @param boolean $throw    (optional) When TRUE, throw an exception(default) when getimagesize() function fails,
     *                                     otherwise when FALSE return the image properties array in which the GD or
     *                                     other error number is stored in the 'errno' key and error message is stored
     *                                     in the 'error' key of the returned array.
     *
     * @return resource Returns an image resource identifier on success, FALSE on errors.
     */
    public function import($file, $phpType = null, $throw = true) {
        /**
         * Attempt to fetch image properties from path if specified..
         * NOTICE: class ImageProperties exposes __invoke() function so may be called as function.
         */
        $imgObj = new ImageProperties();
        $properties = $imgObj($file, $throw);
        if($properties['errno']) {
            $this->lastErrorMsg = $properties['error'];
            $this->lastErrorCode = $properties['errno'];
            if($throw) {
                throw new Exception\RuntimeException($this->lastErrorMsg, $this->lastErrorCode);
            }
            return false;
        }

        if(empty($phpType)) {
            $phpType = $properties["type"];
        }

        $gdFunctions = array(
            IMAGETYPE_JPEG => "imagecreatefromjpeg",
            IMAGETYPE_GIF => "imagecreatefromgif",
            IMAGETYPE_PNG => "imagecreatefrompng",
            MediaConst::IMAGETYPE_PCC_GD2 => "imagecreatefromgd2");
        if(!isset($gdFunctions[$phpType])) {
            // invalid image type
            $typeName = ImageType::getImageType($phpType, true);
            if(empty($typeName)) {
                $typeName = "#" . Types::getVartype($phpType);
            }
            // not a supported image type '%s'
            $msg = sprintf(MediaConst::T_BAD_IMAGE_TYPE, $typeName);
            if(! empty($properties["mime"])) {
                $parts = explode('/', $properties["mime"]);
                if(! empty($parts)) {
                    try {
                        $desc = FileSystem::getFileExtensionDescription(array_pop($parts));
                        if(! empty($desc)) {
                            $msg .= ' - ' . $desc;
                        }
                    } catch (Throwable $ex) {
                    }
                }
            }
            throw new Exception\RuntimeException($msg, MediaConst::E_BAD_TYPE);
        }

        $imageCreateFunction = $gdFunctions[$phpType];
        if(!function_exists($imageCreateFunction)) {
            // cannot manipulate image as image function '%s' is not available
            $msg = sprintf(MediaConst::T_NO_FUNCTION, $imageCreateFunction);
            throw new Exception\RuntimeException($msg, MediaConst::E_NO_FUNCTION);
        }

        // imagecreatefromjpeg() may issue a 'recoverable error' warning or notice that indicates
        // premature end of JPEG file found. Use the '@' error control operator block output.
        // Sample error output:
        // Notice: imagecreatefromjpeg() [function.imagecreatefromjpeg]: gd-jpeg, libjpeg:
        // recoverable error: Premature end of JPEG file in <pathname.php> on line 416
        $phpErrorHandler = new PhpErrorHandler();
        $srcImg = $phpErrorHandler->call(function()use($imageCreateFunction, $file){
            return $imageCreateFunction($file);
        });
        if(! $srcImg) {
            $typeName = ImageType::getImageType($phpType, true);
            if(empty($typeName)) {
                $typeName = "#" . Types::getVartype($phpType);
            }
            $msg = $phpErrorHandler->getErrorMsg("{$imageCreateFunction}() function failed)", "cannot create image using '$imageCreateFunction' for type '{$typeName}'");
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            $this->lastErrorMsg = $msg;
            $this->lastErrorCode = $code;
            if($throw) {
                throw new Exception\RuntimeException($msg, $code);
            }
            return false;
        }
        
        imagealphablending($srcImg, true);
        imagecolortransparent($srcImg, imagecolorallocate($srcImg, 255, 255, 255));            
        return $srcImg;
    }
}
