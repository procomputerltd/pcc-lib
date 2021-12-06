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
    Description : Overlays an image on another image with optional transparency (merge percentage) for watermark
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Types;

/**
 * Overlays an image on another image with optional transparency (merge percentage) for watermarks etc.
 */
class Overlay extends Common {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Doesn't do anything except...overrides the 'overlay' function so 'overlay()' isn't called on instanciation.
    }
    
    /**
     * Merge an overlay image into the destination image.
     *
     * @param resource|string $dstImg  Destination image in which to merge overlayed image.
     * @param string $fileToOverlay    Image file to overlay the Destination image.
     * @param int    $mergePercentage  (optional) Overlay transparency (merge percentage) 0-100%. 0 does NOTHING while 100 overlays the file AS-IS; no transparency.
     * @param int    $overlayOptions   (optional) One or more "IMG_OPTION_OVERLAY_*" values OR-d together.
     * @param int    $overlayAlign     (optional) A "MediaConst::ALIGN_*" value.
     * @param int    $overlayRotate    (optional) Degrees to rotate the overlayed image.
     * @param int    $transparentColor (optional) RGB of the overlay image transparency color.
     * @return boolean
     * @throws Exception\InvalidArgumentException
     */
    public function overlay($dstImg, $fileToOverlay, $mergePercentage = null, $overlayOptions = null, $overlayAlign = null, 
        $overlayRotate = null, $transparentColor = null) {
        if(empty($fileToOverlay)) {
            // no overlay image specified in the overlay property.
            // Cannot overlay image: no overlay image specified.
            throw new Exception\InvalidArgumentException(MediaConst::T_NO_OVERLAY_IMAGE, MediaConst::E_NO_OVERLAY_IMAGE);
        }
        
        if($this->_isGdResource($fileToOverlay)) {
            $ovImg = $fileToOverlay;
        }
        else {
            $importer = new ImportImage();
            $ovImg = $importer->import($fileToOverlay);
        }
        
        $dstWidth = imagesx($dstImg);
        $dstHeight = imagesy($dstImg);
        
        if(null !== $transparentColor) {
//            if(false === $this->setTransparentColor($ovImg, $transparentColor)) {
//                // T_OVERLAY_CANNOT_SET_TRANSPARENT = 'cannot set the transparency color.';
//                // Cannot overlay image: no overlay image specified.
//                throw new Exception\InvalidArgumentException(MediaConst::T_OVERLAY_CANNOT_SET_TRANSPARENT, MediaConst::E_OVERLAY_TRANSPARENT);
//            }
        }

        if(null !== $overlayRotate) {
            $ovImg = $this->_rotate($ovImg, $overlayRotate, $dstWidth, $dstHeight);
        }

        $ovWidth = imagesx($ovImg);
        $ovHeight = imagesy($ovImg);

        /**
         * Determine the X,Y size ratios between the source and overlay images.
         */
        $rx = (float)$dstWidth / $ovWidth;
        $ry = (float)$dstHeight / $ovHeight;
        $overflow = ($rx < 1 || $ry < 1); // The overlay image overflows either X or Y dimension.
        if($overflow && ($overlayOptions & MediaConst::IMG_OPTION_OVERLAY_SIZE_TO_FIT)) {
            if($rx < $ry) {
                $newWidth = $dstWidth;
                $newHeight = ceil($rx * $ovHeight);
            }
            else {
                $newHeight = $dstHeight;
                $newWidth = ceil($ry * $ovWidth);
            }
            $ovImg = $this->_resizeImage($ovImg, $newWidth, $newHeight);
            if(false === $ovImg) {
                return false;
            }
            $ovWidth = imagesx($ovImg);
            $ovHeight = imagesy($ovImg);
        }
        
        if($overlayOptions & MediaConst::IMG_OPTION_OVERLAY_REPEAT) {
            $dstImg = $this->_repeat($dstImg, $ovImg);
        }
        else {
            if(null === $overlayAlign) {
                $x = $y = 0;
            }
            else {
                $imageResize = new ImageResize();
                list($x, $y, $srcX, $srcY) = $imageResize->align($overlayAlign, $dstWidth, $dstHeight, $ovWidth, $ovHeight);
            }

            $phpErrorHandler = new PhpErrorHandler();
            /**
             * A merge value of zero means do NOTHING
             * A merge value of 100 overlays the file AS-IS; no transparency.
             */
            $mergePct = is_numeric($mergePercentage) ? intval($mergePercentage) : null;
            if(null !== $mergePct && 100 !== $mergePct) {
                $this->_colorize($ovImg, $mergePct);
            }
            
            $res = $phpErrorHandler->call(function()use(
                $dstImg,        // Destination image link resource.
                $ovImg,         // Source image link resource.
                $x,             // x-coordinate of destination point.
                $y,             // y-coordinate of destination point.
                $ovWidth,       // source width.
                $ovHeight      // source height.
                ){
                return imagecopy(
                    $dstImg,  // Destination image link resource.
                    $ovImg,   // Source image link resource.
                    $x,       // x-coordinate of destination point.
                    $y,       // y-coordinate of destination point.
                    0,        // x-coordinate of source point.
                    0,        // y-coordinate of source point.
                    $ovWidth, // source width.
                    $ovHeight // source height.
                    );
            });
            
            if(! $res) {
                // T_PHP_FUNCTION_FAILED = image function '%s' failed
                $msg = $phpErrorHandler->getErrorMsg(sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecopy"), "cannot copy overlay image");
                imagedestroy($ovImg);
                // a PHP image function has failed
                throw new Exception\RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
            }
        }
        imagedestroy($ovImg);
        return $res;
    }

    /**
     * Rotate an image n degrees.
     * @param resource  $img          GD image resource to rotate.
     * @param int       $rotateDegrees  # degrees to rotate image.
     * @param int       $dstWidth       (optional) If desired, the dimensions of the rectangle in which the rotated image must fit.
     * @param int       $dstHeight      (optional) ^ ^ ^
     * 
     * @return resource  Returns the rotated image or original image if $degrees is ZERO.
     * 
     * @throws Exception\InvalidArgumentException
     */
    protected function _rotate($img, $rotateDegrees, $dstWidth = null, $dstHeight = null) {
        $degrees = is_numeric($rotateDegrees) ? intval($rotateDegrees) : null;
        if(! is_int($degrees)) {
            $var = Types::getVartype($rotateDegrees);
            throw new Exception\InvalidArgumentException("Invalid overlay rotate degrees value '{$var}'", MediaConst::E_TYPE_MISMATCH);
        }
        if(abs($degrees) > 360) {
            // $var = Types::getVartype($rotateDegrees);
            // throw new Exception\InvalidArgumentException("Invalid overlay value '{$var}'", MediaConst::E_INVALID_ROTATE_PARAM);
        }
        $obj = new Rotate();
        $finalImg = $obj->rotate($img, $degrees, $dstWidth, $dstHeight);
        if(false === $finalImg) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $msg = $phpErrorHandler->getErrorMsg(sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecopy"), "cannot copy overlay image");
            imagedestroy($img);
            // a PHP image function has failed
            throw new Exception\RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
        }
        return $finalImg;
    }

    /**
     * Applies fading to an image 0 to 100.
     * @param resource  $img
     * @param int       $percent
     * @throws Exception\RuntimeException
     */
    protected function _colorize($img, $percent) {
        $phpErrorHandler = new PhpErrorHandler();
        $res = $phpErrorHandler->call(function()use($img){
            return imagealphablending($img, false);
        });
        if(! $res) {
            $function = 'imagealphablending';
        }
        else {
            $res = $phpErrorHandler->call(function()use($img){
                return imagesavealpha($img, true);
            });
            if(! $res) {
                $function = 'imagesavealpha';
            }
            else {
                $res = $phpErrorHandler->call(function()use($img, $percent){
                    // Get a percent value 0 to 100
                    $abs = abs(intval($percent));
                    $pct = $abs ? (($abs - 1) % 100 + 1) : $abs;
                    $alpha = 127 * (1 - $pct / 100);
                    return imagefilter($img, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha);
                });
                if($res) {
                    return true;
                }
                $function = 'imagefilter';
            }
        }
        // T_PHP_FUNCTION_FAILED = image function '%s' failed
        $msg = $phpErrorHandler->getErrorMsg(sprintf(MediaConst::T_PHP_FUNCTION_FAILED, $function), "cannot apply image fading");
        // a PHP image function has failed
        throw new Exception\RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
    }
    
}
