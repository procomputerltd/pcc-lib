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
    Description : Rotates a GD graphics image while sizing its background rectangle to fit the rotated image.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\PhpErrorHandler;
use GdImage;
use stdClass;

class Rotate extends Common {

    /**
     * Since PHP 5.3 __invoke() lets you call an object as a function.
     * Rotates an image while resizing to keep the rotated image in the frame.
     * @param resource  $sourceImg Image to rotate
     * @param int       $angle     Rotation angle. Negative angles apply clock-wise rotation.
     * @return $this|resource
     */
    public function __invoke(?GdImage $sourceImg = null, ?int $angle = null) {
        if(null !== $sourceImg) {
            return $this->rotate($sourceImg, $angle);
        }
        return $this;
    }

    /**
     * Rotates an image while resizing to keep the rotated image in the frame.
     * @param GdImage $sourceImg     Image to rotate
     * @param int     $angleDegrees  Rotation angle.
     * @return resource Returns a GD PHP resource containing the rotated+sized image.
     */
    public function rotate(GdImage $sourceImg, int $angleDegrees) {

        $angle = $angleDegrees;
        if(! $angle || ! (abs($angle) % 360)) {
            return $sourceImg;
        }
        $width = imagesx($sourceImg);
        $height = imagesy($sourceImg);
        if(! $width || ! $height) {
            return $sourceImg;
        }

        $this->_debugSaveImageToFile($sourceImg, 'rotate_a_source');
        
        /**
         * First create a new image that is large enough to hold the original image at any rotation angle.
         */
        $max = hypot($width, $height);
        $img = $this->getGd()->imageCreateTrueColor($max, $max, true);
        
        $this->_debugSaveImageToFile($img, 'rotate_b');
        
        /**
         * Create an image having having dimensions to fully contain the rotated image at the specified angle.
         */
        $rad = deg2rad($angle);
        $x = $height * abs(sin($rad)) + $width * abs(cos($rad));
        $y = $height * abs(cos($rad)) + $width * abs(sin($rad));
        $finalImg = $this->getGd()->imageCreateTrueColor($x, $y, true);
        
        $this->_debugSaveImageToFile($finalImg, 'rotate_c');

        /**
         * Copy the original image centered on the resized new image.
         */
        $this->_copyCentered($img, $sourceImg);
        $libFunc = new stdClass();
        $libFunc->fns = [
            'imagecolorallocatealpha' => [$sourceImg, 0, 0, 0, 127],
            'imagerotate' => [$img, $angle, ''], // $backgroundColor
            'imagecopy' => [$finalImg, '', 0, 0,]
            ];
        $libFunc->errFn = '';
        $phpErrorHandler = new PhpErrorHandler();
        $res = $phpErrorHandler->call(function()use($libFunc, $x, $y){
            foreach($libFunc->fns as $fn => $args) {
                if('imagerotate' === $fn) {
                    // backgroundColor
                    $args[2] = $res;
                }
                elseif('imagecopy' === $fn) {
                    $args[1] = $res;
                    $args[4] = (int)((imagesx($res) - $x) / 2); 
                    $args[5] = (int)((imagesy($res) - $y) / 2);
                    $args[6] = (int)$x;
                    $args[7] = (int)$y;
                }
                $res = call_user_func_array($fn, $args);
                if(false === $res) {
                    $libFunc->errFn = $fn;
                    return false;
                }
            }
            return true;
        });
        if(false !== $res) {
            $this->_debugSaveImageToFile($finalImg, 'rotate_d_final');
            return $finalImg;
        }
        // image function '%s' failed
        $default = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, $libFunc->errFn);
        $msg = $phpErrorHandler->getErrorMsg($default, "cannot rotate image");
        // a PHP image function has failed
        $code = MediaConst::E_PHP_FUNCTION_FAILED;
        throw new Exception\RuntimeException($msg, $code);
    }

    /**
     * Copies an image centered to another image.
     * @param GdImage $dstImg
     * @param GdImage $srcImg
     * @return boolean Return TRUE if success else FALSE.
     */
    protected function _copyCentered(GdImage $dstImg, GdImage $srcImg) {
        $phpErrorHandler = new PhpErrorHandler();
        $res = $phpErrorHandler->call(function()use($dstImg, $srcImg){
            $srcw = imagesx($srcImg);
            $srch = imagesy($srcImg);
            $dstx = (int)((imagesx($dstImg) - $srcw) / 2);
            $dsty = (int)((imagesy($dstImg) - $srch) / 2);
            return imagecopy($dstImg, $srcImg, $dstx, $dsty, 0, 0, $srcw, $srch);
        });
        if(false !== $res) {
            return true;
        }
        // image function '%s' failed
        $default = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, 'imagecopy');
        $msg = $phpErrorHandler->getErrorMsg($default, "cannot copy image");
        // a PHP image function has failed
        $code = MediaConst::E_PHP_FUNCTION_FAILED;
        throw new Exception\RuntimeException($msg, $code);
    }
    
    /**
     * Writes a PNG image file from GdImage parameter.
     * @param GdImage $img
     * @param string  $filename
     * @return void
     */
    protected function _debugSaveImageToFile(GdImage $img, string $filename, string $type = 'png') {
        $dir = getcwd();
        if(is_dir($dir) && is_writable($dir)) {
            // C:\Users\proco\inetpub\laminas\public_html\MyUtilities\tests\temp
            $dir .= "/temp";
            if(! is_dir($dir)) {
                @mkdir($dir);
            }
            if(is_dir($dir) && is_writable($dir)) {
                $phpErrorHandler = new PhpErrorHandler();
                $file = "{$dir}/{$filename}.{$type}";
                try {
                    $phpErrorHandler->call(function()use($img, $file, $type){
                        $quality = 25; // JPEG quality level may be 0-100
                        switch($type) {
                        case 'png':
                            $compression = max(9 - intval(((float)$quality * .9) / 10.0), 0); // PNG compression level may be 0-9
                            imagepng($img, $file, $compression, -1);
                            break;
                        default: // jpg
                            imagejpeg($img, $file, $quality);
                            break;
                        }
                    });
                } catch (Throwable $exc) {
                    $break = 1;
                }
            }
        }
    }
}
