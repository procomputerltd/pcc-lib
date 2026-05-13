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

use Procomputer\Pcclib\Media\MemoryLog;
use Procomputer\Pcclib\Media\Exception\RuntimeException;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Types;
use GdImage;

/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer: Common methods used by Media/image classes.
*/
class ExportImage extends Common {

    /**
     * Saves an image resource to a file.
     *
     * @param GdImage $imgResource  Image resource created by imagecreatetruecolor() or imagecreate() or imagecreatefromstring()
     * @param string  $destFile     The file path to accept the image.
     * @param int     $phpType      Type of image to create. A PHP 'IMAGETYPE_*' image type specifier.
     * @param int     $quality      JPEG quality of the image. Default is 75. 0 = worst quality, smaller file. 100 = best quality, biggest file.
     * @param boolean  $interlace    (optional) Apply interlacing to image.
     *
     * @return mixed Returns the file path name to which the image is written or FALSE on error.
     */
    public function __invoke(GdImage $imgResource, string $destFile, int $phpType, int $quality = null, bool $interlace = false) {
        return $this->export($imgResource, $destFile, $phpType, $quality, $interlace);
    }

    /**
     * Saves an image resource to a file.
     *
     * @param resource $imgResource  Image resource created by imagecreatetruecolor() or imagecreate() or imagecreatefromstring()
     * @param string   $destFile     The file path to accept the image.
     * @param int      $phpType      Type of image to create. A PHP 'IMAGETYPE_*' image type specifier.
     * @param int      $quality      JPEG quality and PNG compression of the image. Default is 75. 0 = worst quality,
     *                               smaller file. 100 = best quality, biggest file.
     * @param boolean  $interlace    (optional) Apply interlacing to image.
     *
     * @return mixed Returns the file path name to which the image is written or FALSE on error.
     */
    public function export(GdImage $imgResource, string $destFile, int $phpType, int $quality = null, bool $interlace = false) {
        $phpErrorHandler = new PhpErrorHandler();

        $width = imagesx($imgResource);
        $height = imagesy($imgResource);
        if(! $this->getMemCheck($width * $height)) {
            $avail = $this->getMemAvailable(true); // true means use number_format()
            $limit = $this->formatBytes($this->getMemLimit()); // true means use number_format()
            $msg = "insufficient memory ({$avail} bytes) to create image having dimensions {$width} x {$height} using GD image" .
                " create function. Consider increasing the PHP memory limit currently {$limit} bytes.";
            throw new RuntimeException($msg, MediaConst::E_MEMORY); // a PHP image function has failed
        }
        
        $typeName = ImageType::getImageType($phpType, true);
        if(empty($typeName)) {
            $typeName = "#" . Types::getVartype($phpType);
        }

        switch($phpType) {
        case IMAGETYPE_JPEG:
        case IMAGETYPE_PNG:
        case IMAGETYPE_GIF:
            // Turn interlace on or off. If interlace fails ignore it for now.
            $phpErrorHandler->call(function()use($imgResource, $interlace){
                return imageinterlace($imgResource, $interlace);
            });
            break;
        }

        $args = [];
        switch($phpType) {
        case IMAGETYPE_JPEG:
            // imagejpeg quality parameter is optional, and ranges from
            // 0 (worst quality, smaller file) to 100 (best quality, biggest file).
            // The default is the default IJG quality value (about 75).
            $quality = (MediaConst::QUALITY_DEFAULT == $quality) ? null : $this->_getValidJpegQuality($quality);
            if(! $quality) {
                $quality = null;
            }
            $args[] = $quality;
            $imageFunction = "imagejpeg";
            break;

        case IMAGETYPE_GIF:
            $imageFunction = "imagegif";
            break;

        case IMAGETYPE_PNG:
            if(MediaConst::QUALITY_DEFAULT == $quality || version_compare(phpversion(), "5.1.2", "<")) {
                $quality = -1;
            }
            else {
                // Quality parameter added in PHP 5.1.2
                $quality = $this->_getValidJpegQuality($quality);
                if(is_numeric($quality)) {
                    // NOTE: for imagepng() quality is compression level from 0 (no compression) to 9.
                    // This differs from imagejeg quality that rages from 0 (worst quality, smaller file) to 100 (best quality, biggest file).
                    $quality = max(9 - intval(((float)$quality * .9) / 10.0), 0);
                }
            }
            /** PNG Filter bit masks.
                PNG_NO_FILTER    (0)
                PNG_FILTER_NONE  (8)   Disable scan-line filtering during PNG creation, resulting in raw image data encoding without predictive filtering. This often increases file size but boosts encoding speed by bypassing algorithms like Paeth, Up, or Average
                PNG_FILTER_SUB   (16)  The filter transmits the difference between each byte and the value of the corresponding byte of the prior pixel.
                PNG_FILTER_UP    (32)  Similar to the Sub filter, except that the pixel immediately above the current pixel, rather than just to its left, is used as the predictor.
                PNG_FILTER_AVG   (64)  The filter uses the average of the two neighboring pixels (left and above) to predict the value of a pixel.
                PNG_FILTER_PAETH (128) The filter computes a simple linear function of the three neighboring pixels (left, above, upper left), then chooses as predictor the neighboring pixel closest to the computed value.
                PNG_ALL_FILTERS  (248) Enables all available PNG filtering algorithms for the encoding process.
            */
            $filt = PNG_FILTER_NONE;
            $args = [$quality, $filt];
            $imageFunction = "imagepng";
            break;

        case IMAGETYPE_BMP:
            $imageFunction = "imagebmp";
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
            $imageFunction = "imagegd2";
            break;

        default:
            if(defined("IMAGETYPE_WEBP") && $phpType === IMAGETYPE_WEBP) {
                // imagejpeg quality parameter is optional, and ranges from
                // 0 (worst quality, smaller file) to 100 (best quality, biggest file).
                // The default is the default IJG quality value (about 75).
                $args[] = (MediaConst::QUALITY_DEFAULT == $quality) ? null : $this->_getValidJpegQuality($quality);
                $imageFunction = "imagewebp";
                break;
            }
            // not a supported image type '%s'
            $errorMsg = sprintf(MediaConst::T_BAD_IMAGE_TYPE, $typeName);
            throw new RuntimeException($errorMsg, MediaConst::E_BAD_TYPE);
        }
        
        $res = $phpErrorHandler->call(function()use($imageFunction, $imgResource, $destFile, $args, $width, $height){
            $args = array_merge([$imgResource, $destFile], $args);
            $res = call_user_func_array($imageFunction, $args);
            if($this->_logging) {
                $log = new MemoryLog();
            }
            if($this->_logging) {
                $log->log("ExportImage::export(): {$imageFunction}", $width * $height);
                unset($log);
            }
            return $res;
        });
        if(! $res) {
            $errorMsg = $phpErrorHandler->getErrorMsg("{$imageFunction}() function failed)",
            "cannot create file from image resource using '$imageFunction' for type '{$typeName}'");
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            throw new RuntimeException($errorMsg, $code);
        }
        return $destFile;
    }

    /**
     * Returns supported export image types.
     * @return array
     */
    public function getSupportedImageTypes(): array {
        $return = [
            IMAGETYPE_JPEG => 'IMAGETYPE_JPEG (jpg)', 
            IMAGETYPE_GIF => 'IMAGETYPE_GIF (gif)',
            IMAGETYPE_PNG => 'IMAGETYPE_PNG (png)',
        ];
        if(defined("IMAGETYPE_WEBP")) {
            $return[IMAGETYPE_WEBP] = 'IMAGETYPE_WEBP (webp)';
        }
        $return[IMAGETYPE_BMP] = 'IMAGETYPE_BMP (bmp)';
        return $return;
    }
    
    /**
     * Validates the 'quality' value for rendering a JPEG image.
     *
     * @param int  $quality  JPEG quality in range 0-100.
     * @param int  $default  (optional) Default quality returned when 'quality' parameter is unspecified or out of range.
     *
     * @return int|mixed Returns a valid JPEG quality value or the default when invalid.
     */
    protected function _getValidJpegQuality(mixed $quality, mixed $default = null) {
        if(is_null($quality) || ! Types::isFloat($quality)) {
            return $default;
        }
        $return = intval($quality);
        return ($return < 0 || $return > 100) ? $default : $return;
    }
}
