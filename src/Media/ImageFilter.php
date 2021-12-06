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
    Description : PHP Software by Pro Computer 
*/

namespace Procomputer\Pcclib\Media;

class ImageFilter {
    /**
     *
     */
    public function applyFilter($imResource, array $imageFilter) {
        foreach($imageFilter as $filterType => $args) {
            /* GD filterType constants. See: php.net/manual/en/function.imagefilter.php

            IMG_FILTER_NEGATE
                Reverses all colors of the image.

            IMG_FILTER_GRAYSCALE
                Converts the image into grayscale.

            IMG_FILTER_BRIGHTNESS
                Changes the brightness of the image. Use arg1 to set the level of
                brightness. -255 = min brightness, 0 = no change, +255 = max brightness

            IMG_FILTER_CONTRAST
                Changes the contrast of the image. Use arg1 to set
                the level of contrast. -100 = max contrast, 0 = no
                change, +100 = min contrast (note the direction!)

            IMG_FILTER_COLORIZE
                Like IMG_FILTER_GRAYSCALE, except you can specify thecolor.
                Use arg1, arg2 and arg3 in the form of red, blue, green and
                arg4 for the alpha channel. The range for each color is 0 to 255.
                Adds (subtracts) specified RGB values to each pixel. The valid
                range for each color is -255...+255, not 0...255. The correct
                order is red, green, blue. -255 = min, 0 = no change, +255 = max

            IMG_FILTER_EDGEDETECT
                Uses edge detection to highlight the edges in the image.

            IMG_FILTER_EMBOSS
                Embosses the image.

            IMG_FILTER_GAUSSIAN_BLUR
                Blurs the image using the Gaussian method.

            IMG_FILTER_SELECTIVE_BLUR
                Blurs the image.

            IMG_FILTER_MEAN_REMOVAL
                Uses mean removal to achieve a "sketchy" effect.

            IMG_FILTER_SMOOTH
                Makes the image smoother. Use arg1 to set the level of smooth-ness.
                Applies a 9-cell convolution matrix where center pixel has the
                weight arg1 and others weight of 1.0. The result is normalized by
                dividing the sum with arg1 + 8.0 (sum of the matrix). Any float is
                accepted, large value (in practice: 2048 or more) = no change
                ImageFilter seem to return false if the argument(s) are out of
                range for the chosen filter
            */
            switch($filterType) {
            case IMG_FILTER_BRIGHTNESS:
            case IMG_FILTER_CONTRAST:
            case IMG_FILTER_SMOOTH:
                //
                // These use a single argument.
                //
                $argsAllowed = 1;
                break;
            case IMG_FILTER_PIXELATE:
                //
                // Use arg1 to set the block size and arg2 to set the pixelation effect mode.
                //
                $argsAllowed = 2;
                break;
            case IMG_FILTER_COLORIZE:
            case IMG_FILTER_GRAYSCALE:
                //
                // Use arg1, arg2 and arg3 in the form of red, green, blue and arg4
                // for the alpha channel. The range for each color is 0 to 255.
                //
                $argsAllowed = 4;
                break;
            default:
                // Unsupported
                $argsAllowed = 0;
                break;
            }

            if(function_exists('error_clear_last')) {
                error_clear_last();
            }
            else {
                // Set error_get_last value to known state,
                set_error_handler('var_dump', 0);
                @$ak9ikKjt6U7; // Uninitialized variable.
                restore_error_handler();                 
                $lastError = error_get_last(); // Retrieve last error to reset error handler.
            }
            global $php_errormsg;
            $php_errormsg = '';
            ini_set('track_errors', 1);
            $argCount = min(is_array($args) ? count($args) : 0, $argsAllowed);
            switch($argCount) {
            case 1:
                $res = imagefilter($imResource, $filterType, $args[0]);
                break;
            case 2:
                $res = imagefilter($imResource, $filterType, $args[0], $args[1]);
                break;
            case 3:
                $res = imagefilter($imResource, $filterType, $args[0], $args[1], $args[2]);
                break;
            case 4:
                $res = imagefilter($imResource, $filterType, $args[0], $args[1], $args[2], $args[3]);
                break;
            default:
                $res = imagefilter($imResource, $filterType, null);
                break;
            }
            ini_restore('track_errors');
            if(false === $res) {
                // a PHP image function has failed
                // T_PHP_FUNCTION_FAILED = image function '%s' failed
                if(method_exists('error_get_last')) {
                    /* error_get_last() return array:
                        [type]      => 8
                        [message]   => The error message.
                        [file]      => C:\WWW\index.php
                        [line]      => 2
                     */
                    $array = error_get_last();
                    $msg = isset($array['message']) ? $array['message'] : '';
                }
                elseif(! empty($php_errormsg)) {
                    $msg = $php_errormsg;
                }
                if(empty($msg)) {
                    $msg = "an unknown error occurred";
                }
                $msg = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagefilter", $msg);
                throw new Exception\InvalidArgumentException($msg, MediaConst::E_PHP_FUNCTION_FAILED);
            }
        }
        return true;
    }
}
