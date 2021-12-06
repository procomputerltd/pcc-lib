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

/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer: Common methods used by media/image classes.
*/
class ExportImage {

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
        return $this->export($imgResource, $destFile, $phpType, $quality, $interlace, $throw);
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
    public function export($imgResource, $destFile, $phpType, $quality, $interlace = 0, $throw = true) {

        $phpErrorHandler = new PhpErrorHandler();

        $typeName = ImageType::getImageType($phpType, true);
        if(empty($typeName)) {
            $typeName = "#" . Types::getVartype($phpType);
        }

        switch($phpType) {
        case IMAGETYPE_JPEG:
            // imagejpeg quality parameter is optional, and ranges from
            // 0 (worst quality, smaller file) to 100 (best quality, biggest file).
            // The default is the default IJG quality value (about 75).

            // If interlace fails ignore it for now.
            $prevInterlace = $phpErrorHandler->call(function()use($imgResource, $interlace){
                return imageinterlace($imgResource, empty($interlace) ? 0 : 1);
            });
            $quality = (MediaConst::QUALITY_DEFAULT == $quality) ? null : $this->_getValidJpegQuality($quality);
            if(is_numeric($quality)) {
                $res = $phpErrorHandler->call(function()use($imgResource, $destFile, $quality){
                    return imagejpeg($imgResource, $destFile, $quality);
                });
            }
            else {
                $res = $phpErrorHandler->call(function()use($imgResource, $destFile){
                    return imagejpeg($imgResource, $destFile);
                });
            }
            if(!$res) {
                $imageFunction = "imagejpeg";
            }
            break;

        case IMAGETYPE_GIF:
            // If interlace fails ignore it for now.
            $prevInterlace = $phpErrorHandler->call(function()use($imgResource, $interlace){
                return imageinterlace($imgResource, empty($interlace) ? 0 : 1);
            });
            $res = $phpErrorHandler->call(function()use($imgResource, $destFile){
                return imagegif($imgResource, $destFile);
            });
            if(!$res) {
                $imageFunction = "imagegif";
            }
            break;

        case IMAGETYPE_PNG:
            // If interlace fails ignore it for now.
            $prevInterlace = $phpErrorHandler->call(function()use($imgResource, $interlace){
                return imageinterlace($imgResource, empty($interlace) ? 0 : 1);
            });
            if(MediaConst::QUALITY_DEFAULT == $quality || version_compare(phpversion(), "5.1.2", "<")) {
                $quality = null;
            }
            else {
                // Quality parameter added in PHP 5.1.2
                $quality = $this->_getValidJpegQuality($quality);
                if(is_numeric($quality)) {
                    // Quality is compression level: from 0 (no compression) to 9.
                    $quality = max(9 - intval(((float)$quality * .9) / 10.0), 0);
                    // PNG_FILTER_NONE     No filtering - the scanline is transmitted unaltered.
                    // PNG_FILTER_SUB      The filter transmits the difference between each byte and the value of the corresponding byte of the prior pixel.
                    // PNG_FILTER_UP       Similar to the Sub filter, except that the pixel immediately above the current pixel, rather than just to its left, is used as the predictor.
                    // PNG_FILTER_AVERAGE  The filter uses the average of the two neighboring pixels (left and above) to predict the value of a pixel.
                    // PNG_FILTER_PAETH    The filter computes a simple linear function of the three neighboring pixels (left, above, upper left), then chooses as predictor the neighboring pixel closest to the computed value.
                    // PNG_FILTER_NONE   A special PNG filter, used by the imagepng() function.
                    // PNG_FILTER_SUB    A special PNG filter, used by the imagepng() function.
                    // PNG_FILTER_UP     A special PNG filter, used by the imagepng() function.
                    // PNG_FILTER_AVG    A special PNG filter, used by the imagepng() function.
                    // PNG_FILTER_PAETH  A special PNG filter, used by the imagepng() function.
                    // PNG_ALL_FILTERS   A special PNG filter, used by the imagepng() function.
                    // PNG_NO_FILTER     A special PNG filter, used by the imagepng() function.
                }
            }
            if($quality) {
                $res = $phpErrorHandler->call(function()use($imgResource, $destFile, $quality){
                    return imagepng($imgResource, $destFile, $quality); // , 0, PNG_ALL_FILTERS) ;
                });
            }
            else {
                $res = $phpErrorHandler->call(function()use($imgResource, $destFile){
                    return imagepng($imgResource, $destFile); // , 0, PNG_ALL_FILTERS) ;
                });
            }
            if(!$res) {
                $imageFunction = "imagepng";
            }
            break;

        case MediaConst::IMAGETYPE_PCC_GD2:
            /* Sample array returned by gd_info():
              ["GD Version"]         => libgd version e.g. "bundled (2.0 compatible)"
              ["FreeType Support"]   => TRUE if Freetype Support is installed.
              ["Freetype Linkage]    => string value describing the way in which Freetype was linked.
              Expected values are: 'with freetype', 'with TTF library', and
              'with unknown library'. The element will only be defined if
              Freetype Support evaluated to TRUE.
              ["T1Lib Support"]      => TRUE if T1Lib support is included.
              ["GIF Read Support"]   => TRUE if support for reading GIF images is included.
              ["GIF Create Support"] => TRUE if support for creating GIF images is included.
              ["JPG Support"]        => TRUE if JPG support is included.
              ["PNG Support"]        => TRUE if PNG support is included.
              ["WBMP Support"]       => TRUE if WBMP support is included.
              ["XBM Support"]        => TRUE if XBM support is included.
              }
             */
            $res = $phpErrorHandler->call(function()use($imgResource, $destFile){
                return imagegd2($imgResource, $destFile);
            });
            if(!$res) {
                $imageFunction = "imagegd2";
            }
            break;

        default:
            $code = MediaConst::E_BAD_TYPE;
            // '%s' is not a supported image type
            $errorMsg = sprintf(MediaConst::T_BAD_IMAGE_TYPE, $typeName);
            $res = false;
        }

        if($res) {
            return $destFile;
        }

        if(!isset($code)) {
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
        }
        if(! isset($errorMsg)) {
            $errorMsg = $phpErrorHandler->getErrorMsg("{$imageFunction}() function failed)",
                "cannot create file from image resource using '$imageFunction' for type '{$typeName}'");
        }
        $this->lastErrorMsg = $errorMsg;
        $this->lastErrorCode = $code;
        if($throw) {
            throw new Exception\RuntimeException($errorMsg, $code);
        }
        return false;
    }
    
    /**
     * Validates the 'quality' value for rendering a JPEG image.
     *
     * @param int  $quality  JPEG quality in range 0-100.
     * @param int  $default  (optional) Default quality returned when 'quality' parameter is unspecified or out of range.
     *
     * @return int|mixed Returns a valid JPEG quality value or the default when invalid.
     */
    protected function _getValidJpegQuality($quality, $default = null) {
        if(is_null($quality) || ! Types::isFloat($quality)) {
            return $default;
        }
        $return = intval($quality);
        return ($return < 0 || $return > 100) ? $default : $return;
    }

}
