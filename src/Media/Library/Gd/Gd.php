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
namespace Procomputer\Pcclib\Media\Library\Gd;

use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\Media\Memory;
use Procomputer\Pcclib\Media\MediaConst;
use Procomputer\Pcclib\Media\Exception\RuntimeException;
use GdImage;
use stdClass;

/**
 * Common class extended by Media/image classes.
 */
class Gd {

    use Memory;

    /**
     * Last error message saved.
     * @var string
     */
    protected $_lastErrorMsg = '';

    /**
     * Last error code saved.
     * @var mixed
     */
    protected $_lastErrorCode = 0;

    /**
     *
     * @var PhpErrorHandler
     */
    protected $_phpErrorHandler;

    /**
     * Options passed to the constructor.
     * @var array
     */
    protected $_options;

    /**
     * Constructor.
     * @param array $options
     */
    public function __construct(array $options = []) {
        $this->_options = $options;
        $this->_phpErrorHandler = new PhpErrorHandler();
    }

    /**
     * Creates an image using imagecreatetruecolor()
     * @param int|float $width
     * @param int|float $height
     * @param bool      $setTransparent (optional) Set transparent background.
     * @return \GdImage
     * @throws RuntimeException
     */
    public function imageCreateTrueColor(int|float $width, int|float $height, bool $setTransparent = false) {
        // Check memory evailable.
        $iWidth = intval(ceil($width));
        $iHeight = intval(ceil($height));
        $size = $iWidth * $iHeight;
        if(! $this->getMemCheck($size, 3, 1.7 + ($setTransparent ? 1 : 0))) {
            $msg = $this->getMemMessage('imagecreatetruecolor', $size);
            throw new RuntimeException($msg, MediaConst::E_MEMORY); // a PHP image function has failed
        }
        $img = $this->_phpErrorHandler->call(function()use($iWidth, $iHeight){
            return imagecreatetruecolor($iWidth, $iHeight);
        });
        if(false === $img) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $errMsg = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecreatetruecolor");
            $msg = $this->_savePhpErrorHandlerMsg($errMsg, "cannot create image");
            // a PHP image function has failed
            throw new RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
        }
        if($setTransparent) {
            $this->setTransparent($img);
        }
        return $img;
    }

    /**
     *
     *
     * NOTE: imagecreatefromjpeg() may issue a 'recoverable error' warning or notice that indicates
     *  premature end of JPEG file found. Use the '@' error control operator block output.
     *  Sample error output:
     *  Notice: imagecreatefromjpeg() [function.imagecreatefromjpeg]: gd-jpeg, libjpeg:
     *  recoverable error: Premature end of JPEG file in <pathname.php> on line 416
     *
     * @param string $imageCreateFunction The Gd function like 'imagecreatefromjpeg'
     * @param string $file The source image file.
     * @return \GdImage
     * @throws RuntimeException
     */
    public function imageCreateFromFile(string $imageCreateFunction, string $file) {
        $size = filesize($file);
        if(! $this->getMemCheck($size, 3, 2)) {
            $msg = $this->getMemMessage($imageCreateFunction, $size, true); // True means it's a file.
            throw new RuntimeException($msg, MediaConst::E_MEMORY); // browser script memory error
        }
        $gdImage = $this->_phpErrorHandler->call(function()use($imageCreateFunction, $file){
            $img = $imageCreateFunction($file);
            return $img;
        });
        return $gdImage;
    }

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
     * @return GdImage Returns the resized GD Image
     * @throws Exception\RuntimeException
     */
    public function resizeImage($img, $width, $height, $srcWidth = null, $srcHeight = null, $srcX = 0, $srcY = 0): GdImage {
        $tempImg = $this->imageCreateTrueColor($width, $height, true);
        if(false === $tempImg) {
            $function = 'imagecreatetruecolor';
        }
        else {
            if(null === $srcWidth) {
                $srcWidth = imagesx($img);
            }
            if(null === $srcHeight) {
                $srcHeight = imagesy($img);
            }
            $res = $this->_phpErrorHandler->call(function()use($tempImg, $img, $width, $height, $srcWidth, $srcHeight, $srcX, $srcY){
                return imagecopyresampled($tempImg, $img, 0, 0, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);
            });
            if(false !== $res) {
                return $tempImg;
            }
            $function = 'imagecopyresampled';
        }
        // image function '%s' failed
        $errMsg = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, $function);
        $msg = $this->_savePhpErrorHandlerMsg("a unknown error occurred", $errMsg);
        if($this->_isGdResource($tempImg)) {
            $this->_imagedestroy($tempImg);
        }
        throw new Exception\RuntimeException($msg, $code);
    }

    /**
     * Uses imagefilter() and IMG_FILTER_COLORIZE the set global opacity to an image 0 to 100.
     * @param GdImage $img     The GD image object.
     * @param int     $percent The percentage of opacity
     * @throws RuntimeException
     */
    public function colorize(GdImage $img, int $percent) {
        // Get a percent value 0 to 100
        $abs = abs(intval($percent));
        $pct = $abs ? (($abs - 1) % 100 + 1) : $abs;
        $alpha = 127 * (1 - $pct / 100);
        $libFunc = new stdClass();
        $libFunc->fns = [
            'imagealphablending' => [$img, false],
            'imagecolorallocatealpha' =>[$img, 0, 0, 0, 127],
            'imagefilter' => [$img, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha],
            'imagesavealpha' => [$img, true]
            ];
        $libFunc->errFn = '';
        $res = $this->_phpErrorHandler->call(function()use($libFunc){
            foreach($libFunc->fns as $fn => $args) {
                if(false === call_user_func_array($fn, $args)) {
                    $libFunc->errFn = $fn;
                    return false;
                }
            }
            return true;
        });
        if(false === $res) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $msg = $this->_savePhpErrorHandlerMsg(sprintf(MediaConst::T_PHP_FUNCTION_FAILED, $libFunc->errFn), "cannot apply image fading");
            // a PHP image function has failed
            throw new RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
        }
    }

    /**
     * Uses imagefill() to set image background to transparent.
     * @param GdImage $img The GD image object.
     * @return bool Returns true on success else false.
     * @throws Exception\RuntimeException
     */
    public function setTransparent(GdImage $img): bool {
        $libFunc = new stdClass();
        $libFunc->fns = [
                'imagealphablending' => [$img, false], // Disable alpha blending to allow full transparency
                'imagesavealpha' => [$img, true], //  Enable alpha channel saving
                'imagecolorallocatealpha' => [$img, 0, 0, 0, 127], // Allocate a fully transparent color (0-127, where 127 is 100% transparent)
                'imagefill' => [$img, 0, 0, ''], // Fill the image with the color.
                'imagealphablending' => [$img, true] // Re-enable blending if you want to draw on top with normal colors
            ];
        $libFunc->errFn = '';
        $res = $this->_phpErrorHandler->call(function()use($libFunc){
            foreach($libFunc->fns as $fn => $args) {
                if('imagefill' === $fn) {
                    $args[3] = $res; // (int) fill color
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
            return true;
        }
        // T_PHP_FUNCTION_FAILED = image function '%s' failed
        $msg = $this->_savePhpErrorHandlerMsg(sprintf(MediaConst::T_PHP_FUNCTION_FAILED, $libFunc->errFn), "cannot create image");
        // a PHP image function has failed
        throw new RuntimeException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
    }

    /**
     * Not implemented: repeat an image across another image.
     * @param GdImage $destImg
     * @param GdImage $overlayedImg
     * @return GdImage
     */
    public function repeat(GdImage $destImg, GdImage $overlayedImg, array $options = []): GdImage {
        return $destImg;
    }

    /**
     * Sets the color that represents transparent in the GdImage
     * @param resource $im     GD image resource.
     * @param int      $color  RGB color value.
     * @return int|boolean The identifier of the new (or current, if none is specified) transparent color is returned. If color is not specified, and the image has no transparent color, the returned identifier will be -1.
     */
    public function setTransparentColor($im, $color) {
        $color = is_numeric($color) ? intval($color) : (Types::isBlank($color) ? null : hexdec((string)$color));
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
        // Get the current transparent color
        $res = imagecolortransparent($im);
        if(false === $res) {
            return false;
        }
        if(empty($res) || -1 == $res) {
            $res = $default;
        }
        return $res;
    }

    /**
     * Frees (destroys) an image resource.
     * @param resource $resource
     * @return boolean Returns TRUE if success else FALSE
     */
    public function imagedestroy($resource) {
        if(! $this->isGdResource($resource)) {
            return false;
        }
        // PhpErrorHandler traps php errors if any and saves to $phpErrHandler->lastError
        return $this->_phpErrorHandler->call(function()use($resource){ return imagedestroy($resource); });
    }

    /**
     * Determines whether the variable represents a GD graphics resource.
     * @param mixed $resource
     * @return boolean Returns TRUE if the variable is an open GD resource else FALSE.
     */
    public function isGdResource($resource) {
        if($resource instanceof GdImage) {
            return true;
        }
        if(is_resource($resource) && 'resource' === gettype($resource)) {
            $type = get_resource_type($resource);
            if(is_string($type) && 'gd' === strtolower($type)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $msg
     * @param string $defaultMsg
     * @param mixed  $code
     * @return string
     */
    protected function _savePhpErrorHandlerMsg(string $msg, string $defaultMsg = "a unknown error occurred", $code = MediaConst::E_PHP_FUNCTION_FAILED): string {
        $return = $this->_phpErrorHandler->getErrorMsg($defaultMsg, $msg);
        $this->_lastErrorMsg = $return;
        $this->_lastErrorCode = $code;
        return $return;
    }

    /**
     * Returns the last saved error message.
     * @return string
     */
    public function getLastErrorMessage(): string {
        return $this->_lastErrorMsg;
    }

    /**
     * Returns the last saved error message.
     * @return mixed
     */
    public function getLastErrorCode(): mixed {
        return $this->_lastErrorCode;
    }
}