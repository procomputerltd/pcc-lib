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
    Description : Image resizing function.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\Types;

class ImageResize {

    /**
     * Sizes and aligns image dimensions and returns an array of new image dimensions.
     *
     * @param int   $sourceWidth    Width of original source image.
     * @param int   $sourceHeight   Height of original source image.
     * @param int   $destWidth      (optional) Width dimension to size image. Null or -1 means auto-size.
     * @param int   $destHeight     (optional) Height dimension to size image. Null or -1 means auto-size.
     * @param int   $sizing         (optional) A "SIZE_*" constant.
     * @param int   $alignment      (optional) An "ALIGN_*" constant.
     *
     * @return array Returns an array with sizing values:<pre>
     * [dstX] => Destination X coordinate
     * [dstY] => Destination Y coordinate
     * [dstW] => Destination Width
     * [dstH] => Destination Height
     * [srcX] => Source X coordinate
     * [srcY] => Source Y coordinate
     * [srcW] => Source Width
     * [srcH] => Source Height</pre>
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(int|float|string $sourceWidth = null, int|float|string $sourceHeight = null, int|float|string $destWidth = null, 
            int|float|string $destHeight = null, int $sizing = null, int $alignment = null) {
        if(null !== $sourceWidth) {
            return $this->resize($sourceWidth, $sourceHeight, $destWidth, $destHeight, $sizing, $alignment);
        }
        return $this;
    }

    /**
     * Sizes and aligns image dimensions and returns an array of new image dimensions.
     *
     * @param int|float $sourceWidth    Width of original source image.
     * @param int|float $sourceHeight   Height of original source image.
     * @param int|float $destWidth      (optional) Width dimension to size image. Null or -1 means auto-size.
     * @param int|float $destHeight     (optional) Height dimension to size image. Null or -1 means auto-size.
     * @param int       $sizing         (optional) A "SIZE_*" constant.
     * @param int       $alignment      (optional) An "ALIGN_*" constant.
     *
     * @return array Returns an array with sizing values:<pre>
     * [dstX] => Destination X coordinate
     * [dstY] => Destination Y coordinate
     * [dstW] => Destination Width
     * [dstH] => Destination Height
     * [srcX] => Source X coordinate
     * [srcY] => Source Y coordinate
     * [srcW] => Source Width
     * [srcH] => Source Height</pre>
     *
     * @throws Exception\InvalidArgumentException
     */
    public function resize(int|float|string $sourceWidth, int|float|string $sourceHeight, int|float|string $destWidth = null, 
        int|float|string $destHeight = null, int $sizing = null, int $alignment = null) {

        /**
         * Validate the source image dimensions are > 0.
         */
        $res = $this->_checkDimensions($sourceWidth, $sourceHeight, 1);
        if($res->error) {
            throw new Exception\InvalidArgumentException($res->error, MediaConst::E_IMAGE_SIZE_INVALID);
        }
        $srcW = intval($sourceWidth);
        $srcH = intval($sourceHeight);

        /**
         * Resolve and validate the destination dimensions. If the dimensions are null or -1 use proportional dimensions.
         *
         */
        $msgs = [];
        if(is_null($destWidth)) {
            $dstW = -1;
        }
        else {
            $dstW = intval($destWidth);
            if($dstW != -1 && $dstW < 1) {
                $msgs["destWidth"] = $destWidth;
            }
        }
        if(is_null($destHeight)) {
            $dstH = -1;
        }
        else {
            $dstH = intval($destHeight);
            if($dstH != -1 && $dstH < 1) {
                $msgs["destHeight"] = $destHeight;
            }
        }
        if(count($msgs)) {
            // E_INVALID_SIZE_PARAM
            //    invalid size parameter
            // T_PARAMETER_INVALID
            //    invalid '%s' parameter '%s'
            $msg = sprintf(MediaConst::T_PARAMETER_INVALID, implode(', ', array_keys($msgs)), implode(', ', $msgs))
                . ": expecting positive integer or null or -1 when auto-sizing is desired.";
            throw new Exception\InvalidArgumentException($msg, MediaConst::E_INVALID_SIZE_PARAM);
        }

        /* If both dest Width, Height are unspecified use the source
           image dimensions. Or, when one or the other is specified,
           calculate size for that dimension to keep size proportional
           to source image dimensions.
         */
        if(-1 == $dstW && -1 == $dstH) {
            // Dimensions unspecified, automatic. Source, dest dimensions identical.
            // No sizing nor alignment needed.
            // Developer may be simply adjusting interlace, quality and/or image type like jpg to png.
            $dstW = $srcW;
            $dstH = $srcH;
            $dstX = $dstY = $srcX = $srcY = 0;
        }
        else {
            if(-1 == $dstW) {
                // Source, dest width proportional, not identical.
                $dstW = (int)((float)$dstH / $srcH * $srcW);
            }
            elseif(-1 == $dstH) {
                // Source, dest height proportional, not identical.
                $dstH = (int)((float)$dstW / $srcW * $srcH);
            }
            if($dstW === $srcW && $dstH === $srcH) {
                // Dimensions identical. No sizing nor alignment needed.
                $dstX = $dstY = $srcX = $srcY = 0;
            }
            else {
                if(null !== $sizing) {
                    // Size the image to the destination boundaries.
                    list($dstW, $dstH, $srcW, $srcH) = $this->size($sizing, $dstW, $dstH, $srcW, $srcH);
                }

                if(null === $alignment) {
                    $dstX = $dstY = $srcX = $srcY = 0;
                }
                else {
                    // Align the image in the destination boundaries.
                    list($dstX, $dstY, $srcX, $srcY) = $this->align($alignment, $dstW, $dstH, $srcW, $srcH);
                }
            }
        }

        return [
            "dstX" => $dstX, // Destination X coordinate
            "dstY" => $dstY, // Destination Y coordinate
            "dstW" => $dstW, // Destination Width
            "dstH" => $dstH, // Destination Height
            "srcX" => $srcX, // Source X coordinate.
            "srcY" => $srcY, // Source Y coordinate.
            "srcW" => $srcW, // Source Width
            "srcH" => $srcH  // Source Height
            ];
    }

    /**
     *
     * @param int       $sizing         A 'SIZE_*' constant
     * @param int|float $destWidth      Destination image width.
     * @param int|float $destHeight     Destination image height.
     * @param int|float $sourceWidth    Source image width.
     * @param int|float $sourceHeight   Source image height.
     * @return array Returns 4-dim array of sizes.
     *
     * @throws Exception\InvalidArgumentException
     */
    public function size(int $sizing, int|float|string $destWidth, int|float|string $destHeight, int|float|string $sourceWidth, 
        int|float|string $sourceHeight) {

        /**
         * Validate all image dimensions are > 0.
         */
        $d = $this->_checkDimensions($destWidth, $destHeight, 1);
        $s = $this->_checkDimensions($sourceWidth, $sourceHeight, 1);
        if($d->error || $s->error) {
            $msgs = [$d->error ? $d->error : '', $s->error ? $s->error : ''];
            throw new Exception\InvalidArgumentException(implode(': ', $msgs), MediaConst::E_IMAGE_SIZE_INVALID);
        }
        $dstW = $d->x;
        $dstH = $d->y;
        $srcW = $s->x;
        $srcH = $s->y;

        $diffX = $dstW - $srcW;
        $diffY = $dstH - $srcH;
        switch($sizing) {
        case MediaConst::SIZE_SHRINK:
            // Shrink the height, width of the object to fill the frame; may distort the image.
            // The GD (Graphics Draw) functions will "stretch" the source image dimensions to fit the dest dimensions.
            if($diffX >= 0 && $diffY >= 0) {
                // No need to shrink; source rectangle already fits inside dest rect.
                $dstW = $srcW;
                $dstH = $srcH;
                break;
            }
            // FALLTHROUGH !
        case MediaConst::SIZE_ZOOM:
            // Display the entire object, resizing it as necessary without
            // distorting the image. May result in void areas of the frame.
            if($diffX || $diffY) {
                $rx = (float)$dstW / $srcW;
                $ry = (float)$dstH / $srcH;
                if($ry < $rx) {
                    $rx = $ry;
                }
                $dstW = (int)ceil($rx * $srcW);
                $dstH = (int)ceil($rx * $srcH);
            }
            else {
                $dstW = $srcW;
                $dstH = $srcH;
            }
            break;
        case MediaConst::SIZE_CLIP:
            // If the object is larger than the frame it is clipped on the right and bottom of the frame's edges.
            if($diffX < 0) {
                $srcW = $dstW;
            }
            else {
                $dstW = $srcW;
            }
            if($diffY < 0) {
                $srcH = $dstH;
            }
            else {
                $dstH = $srcH;
            }
            break;
        case MediaConst::SIZE_STRETCH:
            // Stretch (or shrink) the height, width of the object to fill the frame; may distort the image.
            // The GD (Graphics Draw) functions will "stretch" the source image dimensions to fit the dest dimensions.
            break;
        default:
            // invalid image sizing parameter
            // invalid '%s' parameter '%s'
            $msg = sprintf(MediaConst::T_PARAMETER_INVALID, "sizing", Types::getVartype($sizing));
            throw new Exception\InvalidArgumentException($msg, MediaConst::E_INVALID_SIZE_PARAM);
        }
        return [
            $dstW,
            $dstH,
            $srcW,
            $srcH
            ];
    }

    /**
     * Aligns a rectangle (image) inside another rectangle.
     * @param int       $align A 'ALIGN_*' constant.
     * @param int|float $destWidth       Destination image width.
     * @param int|float $destHeight      Destination image height.
     * @param int|float $sourceWidth     Source image width.
     * @param int|float $sourceHeight    Source image height.
     * @return array Returns an 2-element array: [X,Y] offsets.
     */
    public function align(int $align, int|float|string $destWidth, int|float|string $destHeight, int|float|string $sourceWidth, int|float|string $sourceHeight) {

        $d = $this->_checkDimensions($destWidth, $destHeight, 1);
        $s = $this->_checkDimensions($sourceWidth, $sourceHeight, 1);
        if($d->error || $s->error) {
            $msgs = [$d->error ? $d->error : '', $s->error ? $s->error : ''];
            throw new Exception\InvalidArgumentException(implode(': ', $msgs), MediaConst::E_IMAGE_SIZE_INVALID);
        }
        $dstW = $d->x;
        $dstH = $d->y;
        $srcW = $s->x;
        $srcH = $s->y;
        
        $diffX = $dstW - $srcW;
        $diffY = $dstH - $srcH;
        $x = ($diffX < 1) ? 0 : $diffX ;
        $y = ($diffY < 1) ? 0 : $diffY;
        $srcX = $srcY = 0;
        if($x || $y) {
            switch($align) {
            case MediaConst::ALIGN_CENTER:
                if($x) {
                    $x /= 2;
                }
                if($y) {
                    $y /= 2;
                }
                break;
            case MediaConst::ALIGN_TOPRIGHT:
                $y = 0;
                break;
            case MediaConst::ALIGN_TOPCENTER:
                $y = 0;
                // FALL THROUGH!
            case MediaConst::ALIGN_BOTTOMCENTER:
                if($x) {
                    $x /= 2;
                }
                break;
            case MediaConst::ALIGN_BOTTOMLEFT:
                $x = 0;
                break;
            case MediaConst::ALIGN_BOTTOMRIGHT:
                // Already have proper X,Y
                break;
            default: // MediaConst::ALIGN_TOPLEFT:
                $x = $y = 0;
            }
        }
        return [
            $x,
            $y,
            $srcX,
            $srcY];
    }

    /**
     * Resolves/validates x,y integers
     * @param int|float $x
     * @param int|float $y
     * @param int       $min
     * @throws Exception\InvalidArgumentException
     */
    protected function _checkDimensions(int|float|string $x, int|float|string $y, int $min = 0) {
        $return = new \stdClass();
        if(is_string($x)) {
            $x = trim($x);
        }
        if(is_string($y)) {
            $y = trim($y);
        }
        $valid = false;
        if(is_numeric($x)) {
            $return->x = intval($x);
            $valid = $return->x >= $min;
        }
        if(is_numeric($y)) {
            $return->y = intval($y);
            if($valid) {
                $valid = $return->y >= $min;
            }
        }
        $return->error = $valid ? false :
            // E_IMAGE_SIZE_INVALID invalid image dimensions
            // T_IMAGE_SIZE_INVALID invalid image x(%s) and/or y(%s) parameters
            sprintf(MediaConst::T_IMAGE_SIZE_INVALID, Types::getVartype($x), Types::getVartype($y)) . ": expecting value >= {$min}";
        return $return;
    }
}
