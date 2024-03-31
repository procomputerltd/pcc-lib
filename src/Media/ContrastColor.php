<?php
/* 
 * Copyright (C) 2023 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
namespace Procomputer\Pcclib\Media;

class ContrastColor {
    
    const RGB_NAVY = '000080';
    const RGB_BLACK = '000000';
    const RGB_WHITE = 'ffffff';
    
    /**
     *
     * @param string|int $color      RGB Color specifier.
     * @param boolean    $addPrefix  (optional) Add the leading '#' to the result
     * 
     * @return string
     */
    function getContrastColor(mixed $color, $addPrefix = true): string {
        $rgbColor = false;
        if(is_string($color)) {
            // remove leading # and
            $color = preg_replace('/^[# \\s]*(.*)\\s*$/', '$1', $color) ;
            if(strlen($color) && preg_match('/^([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color)) {
                $rgbColor = $color;
            }
        }
        if(false === $rgbColor && is_numeric($color)) {
            $color = intval($color);
            if($color > 0) {
                $rgbColor = substr('000000' . dechex($color), -6);
            }
        }

        if(false === $rgbColor) {
            // The default color.
            $rgbColor = self::RGB_NAVY;
        }
        else {
            /**
             * If RBG parse-able value determine the luminosity and select contrasting black or white.
             */
            $rgb = [];
            $len = strlen($rgbColor);
            $incr = ($len < 6) ? 1 : 2;
            for($i = 0; $i < $len; $i += $incr) {
                $rgb[] = hexdec(substr($rgbColor, $i, $incr));
            }
            $squared_contrast = (
                $rgb[0] * $rgb[0] * .299 +
                $rgb[1] * $rgb[1] * .587 +
                $rgb[2] * $rgb[2] * .114
            );
            $rgbColor = ($squared_contrast > pow(130, 2)) ? self::RGB_BLACK : self::RGB_WHITE;
        }
        if($addPrefix) {
            $rgbColor = '#' . $rgbColor;
        }
        return $rgbColor;
    }
}