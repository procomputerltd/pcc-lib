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

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\FileSystem;

/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer: Common methods used by Media/Image classes.
*/
class ImportImage extends Common {

    /**
     * Creates an image resource from a file.
     *
     * @param string  $file     File path from which to create image.
     * @param int     $phpType  (optional) PHP 'IMAGETYPE_*' constant.
     * @return mixed Returns the file path name to which the image is written or FALSE on error.
     */
    public function __invoke(string $file, int $phpType = null) {
        return $this->import($file, $phpType);
    }

    /**
     * Creates an image resource from a file.
     *
     * @param string  $file     Path of file from which to create image.
     * @param int     $phpType  (optional) PHP 'IMAGETYPE_*' constant. If unspecified the ImageProperties() is used to determine type.
     * @return resource|bool Returns an image resource identifier on success, FALSE on errors.
     */
    public function import(string $file, int $phpType = null) {
        /**
         * Attempt to fetch image properties from path if specified..
         * NOTICE: class ImageProperties exposes __invoke() function so may be called as function.
         */
        $imgObj = new ImageProperties();
        $properties = $imgObj($file);
        if($properties['errno']) {
            $this->lastErrorCode = $properties['errno'];
            $this->lastErrorMsg = $properties['error'] ?? '';
            if(empty($this->lastErrorMsg)) {
                $this->lastErrorMsg = "cannot import image: an unknown error ocurred.";
            }
            return false;
        }

        if(empty($phpType)) {
            $phpType = $properties["type"];
        }

        $gdFunctions = $this->getSupportedImageFunctions();
        /* IMAGETYPE_JPEG = imagecreatefromjpeg
           IMAGETYPE_GIF  = imagecreatefromgif
           IMAGETYPE_PNG  = imagecreatefrompng
           IMAGETYPE_WEBP = imagecreatefromwebp
           IMAGETYPE_BMP  = imagecreatefrombmp
         */
        if(!isset($gdFunctions[$phpType])) {
            $this->lastErrorCode = MediaConst::E_BAD_TYPE;
            $this->lastErrorMsg = $this->_getBadPhpTypeErrorMsg($phpType, $properties["mime"] ?? '');
            return false;
        }

        $imageCreateFunction = $gdFunctions[$phpType];
        if(!function_exists($imageCreateFunction)) {
            // cannot manipulate image as image function '%s' is not available
            $msg = sprintf(MediaConst::T_NO_FUNCTION, $imageCreateFunction);
            $this->lastErrorMsg = $msg;
            $this->lastErrorCode = MediaConst::E_NO_FUNCTION;
            return false;
        }
        
        // imagecreatefromjpeg() may issue a 'recoverable error' warning or notice that indicates
        // premature end of JPEG file found. Use the '@' error control operator block output.
        // Sample error output:
        // Notice: imagecreatefromjpeg() [function.imagecreatefromjpeg]: gd-jpeg, libjpeg:
        // recoverable error: Premature end of JPEG file in <pathname.php> on line 416
        $gdImage = $this->getGd()->imageCreateFromFile($imageCreateFunction, $file);
        if(! $gdImage) {
            $typeName = ImageType::getImageType($phpType, true);
            if(empty($typeName)) {
                $typeName = "#" . Types::getVartype($phpType);
            }
            $msg = $this->_phpErrorHandler->getErrorMsg("{$imageCreateFunction}() function failed", 
                "cannot create image using '$imageCreateFunction' for type '{$typeName}'");
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            $this->lastErrorMsg = $msg;
            $this->lastErrorCode = $code;
            return false;
        }
        return $gdImage;
    }
    
    /**
     * Returns supported export image types.
     * @return array
     */
    public function getSupportedImageFunctions(): array {
        $gdFunctions = array(
            "IMAGETYPE_JPEG" => "imagecreatefromjpeg",
            "IMAGETYPE_GIF" => "imagecreatefromgif",
            "IMAGETYPE_PNG" => "imagecreatefrompng",
            "IMAGETYPE_WEBP" => "imagecreatefromwebp",
            "IMAGETYPE_BMP" => "imagecreatefrombmp");
        $return = [];
        foreach($gdFunctions as $type => $function) {
            if(defined($type)) {
               $return[constant($type)] = $function;
            }
        }
        $return[MediaConst::IMAGETYPE_PCC_GD2] = "imagecreatefromgd2";
        return $return;
    }
    
    private function _getBadPhpTypeErrorMsg($phpType, $mimeType = '') {
        // invalid image type
        $typeName = ImageType::getImageType($phpType, true);
        if(empty($typeName)) {
            $typeName = "#" . Types::getVartype($phpType);
        }
        // not a supported image type '%s'
        return sprintf(MediaConst::T_BAD_IMAGE_TYPE, $typeName) . $this->_getMimeTypeDescription($mimeType);
    }
    
    private function _getMimeTypeDescription($mimeType) {
        if(! empty($mimeType)) {
            $parts = explode('/', $mimeType);
            if(! empty($parts)) {
                try {
                    $desc = FileSystem::getFileTypeDescription(array_pop($parts));
                    if(! empty($desc)) {
                        return ' - ' . $desc;
                    }
                } catch (Throwable $ex) {
                }
            }
        }
        return '';
    }
}
