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

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Media\Exception\InvalidArgumentException;
use Procomputer\Pcclib\Media\Exception\RuntimeException;
use GdImage;

/**
 * Overlays an image on another image with optional transparency (merge percentage) for watermarks etc.
 */
class Overlay extends Common {

    /**
     * Merge an overlay image into the destination image.
     *
     * @param GdImage         $dstImg           Destination image in which to merge overlayed image.
     * @param string|GdImage  $fileToOverlay    Image file or GD resource to overlay the Destination image.
     * @param int             $mergePercentage  (optional) Overlay transparency (merge percentage) 0-100%. 0 does NOTHING while 100 overlays the file AS-IS; no transparency.
     * @param int             $overlayOptions   (optional) One or more "IMG_OPTION_OVERLAY_*" values OR-d together.
     * @param int             $overlayAlign     (optional) A "MediaConst::ALIGN_*" value.
     * @param int             $overlayRotate    (optional) Degrees to rotate the overlayed image.
     * @param int             $transparentColor (optional) RGB of the overlay image transparency color.
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function overlay(GdImage $dstImg, string|GdImage $fileToOverlay, int|float|string $mergePercentage = 0, 
            int|float|string $overlayOptions = null, int|float|string $overlayAlign = MediaConst::ALIGN_NONE, int|float|string $overlayRotate = 0, 
            int|float|string $transparentColor = null) {
        if(Types::isBlank($fileToOverlay)) {
            // no overlay image specified in the overlay property.
            // Cannot overlay image: no overlay image specified.
            throw new InvalidArgumentException(MediaConst::T_NO_OVERLAY_IMAGE, MediaConst::E_NO_OVERLAY_IMAGE);
        }

        $phpErrorHandler = new PhpErrorHandler();
        
        if(! $fileToOverlay instanceof GdImage) {
            $importer = new ImportImage();
            $importer->setLogging($this->getLogging());
            $ovImg = $importer->import($fileToOverlay);
        }
            
        $dstWidth = imagesx($dstImg);
        $dstHeight = imagesy($dstImg);

        if(null !== $transparentColor) {
            if(false === $this->getGd()->setTransparentColor($ovImg, $transparentColor)) {
                // T_OVERLAY_CANNOT_SET_TRANSPARENT = 'cannot set the transparency color.';
                // Cannot overlay image: no overlay image specified.
                throw new InvalidArgumentException(MediaConst::T_OVERLAY_CANNOT_SET_TRANSPARENT, MediaConst::E_OVERLAY_TRANSPARENT);
            }
        }

        $degrees = is_numeric($overlayRotate) ? intval($overlayRotate) : null;
        if(! is_int($degrees)) {
            $var = Types::getVartype($overlayRotate);
            throw new InvalidArgumentException("Invalid overlay rotate degrees value '{$var}'", MediaConst::E_TYPE_MISMATCH);
        }
        if($degrees) {
            $obj = new Rotate();
            $obj->setLogging($this->getLogging());
            $ovImg = $obj->rotate($ovImg, $degrees);
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
            $ovImg = $this->getGd()->resizeImage($ovImg, $newWidth, $newHeight);
            if(false === $ovImg) {
                return false;
            }
            $ovWidth = imagesx($ovImg);
            $ovHeight = imagesy($ovImg);
        }

        if($overlayOptions & MediaConst::IMG_OPTION_OVERLAY_REPEAT) {
            $res = $this->getGd()->repeat($dstImg, $ovImg);
        }
        else {
            if(null === $overlayAlign) {
                $x = $y = 0;
            }
            else {
                $imageResize = new ImageResizeAlign();
                list($x, $y, $srcX, $srcY) = $imageResize->align($overlayAlign, $dstWidth, $dstHeight, $ovWidth, $ovHeight);
            }

            /**
             * A merge value of zero means do NOTHING
             * A merge value of 100 overlays the file AS-IS; no transparency.
             */
            $mergePct = is_numeric($mergePercentage) ? intval($mergePercentage) : null;
            if(null !== $mergePct && $mergePct > 0 && 100 !== $mergePct) {
                $this->getGd()->colorize($ovImg, $mergePct);
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
                throw new RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
            }
        }
        $phpErrorHandler->call(function()use($ovImg){
            imagedestroy($ovImg);
        });
        return $res;
    }
}
