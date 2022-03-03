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
    Description : Common class extended by Media/image classes.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Types;

/**
 * Common class extended by Media/image classes.
 */
class Common {

    public $lastErrorMsg = '';
    public $lastErrorCode = 0;

    /**
     * Resizes an image and returns a new resized GD image resource.
     *
     * @param resource  $img        GD Image resource to resize
     * @param int       $width      The width to resize the image.
     * @param int       $height     The height to resize the image.
     * @param int       $srcWidth   $srcWidth and $srcHeight specify the dimensions of the section of the image to resize. Default is entire image.
     * @param int       $srcHeight  (see $srcWidth)
     * @param int       $srcX       $srcX and $srcY specify the top-left coordinates of the section of the image to resize. Default is 0,0 top-left corner.
     * @param int       $srcY       (see $srcX)  
     * @return resource Returns the resized GD Image resource
     * @throws Exception\RuntimeException
     */
    protected function _resizeImage($img, $width, $height, $srcWidth = null, $srcHeight = null, $srcX = 0, $srcY = 0) {
        $phpErrorHandler = new PhpErrorHandler();
        $tempImg = $phpErrorHandler->call(function()use($width, $height){
            return imagecreatetruecolor($width, $height);
        });
        if(false === $tempImg) {
            $function = 'imagecreatetruecolor';
        }
        else {
            $res = $phpErrorHandler->call(function()use($tempImg){
                return imagecolorallocatealpha($tempImg, 0, 0, 0, 127);
            });
            if(false === $res) {
                $function = 'imagecolorallocate';
            }
            else {
                $res = $phpErrorHandler->call(function()use($tempImg, $res){
                    return imagefill($tempImg, 0, 0, $res);
                });
                if(false === $res) {
                    $function = 'imagefill';
                }
                else {
                    if(null === $srcWidth) {
                        $srcWidth = imagesx($img);
                    }
                    if(null === $srcHeight) {
                        $srcHeight = imagesy($img);
                    }
                    $res = $phpErrorHandler->call(function()use($tempImg, $img, $width, $height, $srcWidth, $srcHeight, $srcX, $srcY){
                        return imagecopyresampled($tempImg, $img, 0, 0, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);
                    });
                    if(false !== $res) {
                        return $tempImg;
                    }
                    $function = 'imagecopyresampled';
                }
            }
        }
        // a PHP image function has failed
        $code = MediaConst::E_PHP_FUNCTION_FAILED;
        // image function '%s' failed
        $errMsg = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, $function);
        $msg = $phpErrorHandler->getErrorMsg("a unknown error occurred", $errMsg);
        $this->lastErrorMsg = $errorMsg;
        $this->lastErrorCode = $code;
        if($this->_isGdResource($tempImg)) {
            $this->_imagedestroy($tempImg);
        }
        throw new Exception\RuntimeException($errorMsg, $code);
    }

    /**
     * Frees (destroys) an image resource.
     * @param resource $resource
     * @return boolean Returns TRUE if success else FALSE
     */
    protected function _imagedestroy($resource) {
        if(! $this->_isGdResource($resource)) {
            return false;
        }
        // PhpErrorHandler traps php errors if any and saves to $phpErrHandler->lastError
        $phpErrHandler = new PhpErrorHandler();
        return $phpErrHandler->call(function()use($resource){ return imagedestroy($resource); });
    }

    /**
     * Determines whether the variable represents a GD graphics resource.
     * @param mixed $resource
     * @return boolean Returns TRUE if the variable is an open GD resource else FALSE.
     */
    protected function _isGdResource($resource) {
        if(is_resource($resource) && 'resource' === gettype($resource)) {
            $type = get_resource_type($resource);
            if(is_string($type) && 'gd' === strtolower($type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set the color that represents transparent
     * @param resource $im     GD image resource.
     * @param int      $color  RGB color value.
     * @return int|boolean The identifier of the new (or current, if none is specified) transparent color is returned. If color is not specified, and the image has no transparent color, the returned identifier will be -1.
     */
    public function setTransparentColor($im, $color) {
        $color = is_numeric($color) ? intval($color)
            : (Types::isBlank($color) ? null : hexdec((string)$color));
        if(null === $color) {
            return false;
        }
        $i = intval($color);
        $r = ($i >> 16) & 0xff;
        $g = ($i >> 8) & 0xff;
        $b = $i & 0xff;
        $color = imagecolorexact($im, $r, $g, $b);
        if(! $color) {
            return false;
        }
        $res = imagecolortransparent($im, $color);
        return $res;
    }

    /**
     * Returns the color that represents transparent
     * @param resource $im     GD image resource.
     * @return int|boolean The transparent color is returned.
     */
    public function getTransparentColor($im, $default = -1) {
        $res = imagecolortransparent($im);
        if(false === $res) {
            return false;
        }
        if(empty($res) || -1 == $res) {
            $res = $default;
        }
        return $res;
    }
}

