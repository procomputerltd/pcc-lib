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

class Rotate extends Common {
    
    public function __construct() {
    }
    
    /**
     * Since PHP 5.3 __invoke() lets you call an object as a function. 
     * Rotates an image while resizing to keep the rotated image in the frame.
     * @param resource  $sourceImg Image to rotate
     * @param int       $angle     Rotation angle. Negative angles apply clock-wise rotation.
     * @return $this|resource
     */
    public function __invoke($sourceImg = null, $angle = null) {
        if(null !== $sourceImg) {
            return $this->rotate($sourceImg, $angle);
        }
        return $this;
    }
    
    /**
     * Rotates an image while resizing to keep the rotated image in the frame.
     * @param resource  $sourceImg     Image to rotate
     * @param int       $angleDegrees  Rotation angle.
     * @return resource Returns a GD PHP resource containing the rotated+sized image.
     */
    public function rotate($sourceImg, $angleDegrees) {

        $angle = (int)$angleDegrees;
        if(! $angle || ! abs($angle) % 360) {
            return $sourceImg;
        }
        $width = imagesx($sourceImg);
        $height = imagesy($sourceImg);
        
        /**
         * First create a new image that is large enough to hold the original image at any rotation angle.
         */
        $max = hypot($width, $height);
        $img = $this->_createImage($max, $max);
        if(false === $img) {
            return false;
        }
        /**
         * DEBUG
         */
        // $debugIndex = $this->_debugWriteToFile($img);
        
        /**
         * Copy the original image centered on the new image.
         */
        if(false === $this->_copyCentered($img, $sourceImg)) {
            return false;
        }
        
        /**
         * DEBUG
         */
        // $debugIndex = $this->_debugWriteToFile($img, $debugIndex);
        
        /**
         * Rotate the new image.
         * 
         * NOTICE: negative angles to apply clock-wise rotation.
         */
        $rotatedImg = imagerotate($img, $angle, imagecolorallocatealpha($sourceImg, 0, 0, 0, 127));
        if(false === $rotatedImg) {
            return false;
        }
        
        /**
         * DEBUG
         * $debugIndex = $this->_debugWriteToFile($rotatedImg, $debugIndex);
         */
        
        /**
         * Create an image having having dimensions to fully contain the rotated image at the specified angle.
         */
        $rad = deg2rad($angle);
        $x = $height * abs(sin($rad)) + $width * abs(cos($rad));
        $y = $height * abs(cos($rad)) + $width * abs(sin($rad));
        $finalImg = $this->_createImage($x, $y);
        if(false === $finalImg) {
            return false;
        }
        $res = imagecopy(
            $finalImg, 
            $rotatedImg, 
            0, 
            0, 
            (imagesx($rotatedImg) - $x) / 2,
            (imagesy($rotatedImg) - $y) / 2,
            $x,
            $y);
        if(false === $res) {
            return false;
        }
        /**
         * DEBUG
         * $this->_debugWriteToFile($finalImg, $debugIndex);
         */
        return $finalImg;
    }
    
    /**
     * Copies an image centered to another image.
     * @param resource $dstImg
     * @param resource $srcImg
     * @return boolean Return TRUE if success else FALSE.
     */
    protected function _copyCentered($dstImg, $srcImg) {
        
        $w = imagesx($srcImg);
        $h = imagesy($srcImg);
        $res = imagecopy(
            $dstImg, 
            $srcImg, 
            (imagesx($dstImg) - $w) / 2,
            (imagesy($dstImg) - $h) / 2,
            0, 
            0, 
            $w,
            $h);
        return $res;
    }

    /**
     * Create image with transparency.
     * @param int $width  Image width pixels.
     * @param int $height Image height pixels.
     * @return resource|boolean Return GD image resource or FALSE on error.
     */
    protected function _createImage($width, $height, $backgroundColor = null) {
        $img = imagecreatetruecolor($width, $height);
        if(false === $img) {
            return false;
        }
        if(false === imagesavealpha($img, true)) {
            return false;
        }
        // imagealphablending($img, false);
        if(!is_numeric($backgroundColor)) {
            $backgroundColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
            if(false === $backgroundColor) {
                return false;
            }
        }
        if(false === imagefill($img, 0, 0, $backgroundColor)) {
            return false;
        }
        return $img;
    }
    
    /**
     * 
     * @param resource $img Image to write to an image file.
     * @param int      (optional)$debugIndex 
     * @return type
     */
    protected function _debugWriteToFile($img, $debugIndex = 1) {
        // Disabled
        $file = "C:/Users/JIMBO/temp/rotate_{$debugIndex}.jpg";
        imagejpeg($img, $file, 50);
        return ++$debugIndex;
    }

}
