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
    Description : Returns a file extension associated with an 'IMAGETYPE_*' value, OR, returns a IMAGETYPE_*' value for the given file extension.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\FileSystem;

/**
 * Returns a file extension associated with an 'IMAGETYPE_*' value, OR, returns a IMAGETYPE_*' value for the given file extension.
 */
class ImageType {

    /**
     * Converts PHP 'IMAGETYPE_*' integer constant to its associated string file extension and vice-versa.
     *
     * @param int     $value            The value to convert; either PHP 'IMAGETYPE_*' integer constant or
     *                                  a file extension e.g. 'jpeg' and 'gif'
     *
     * @param boolean $fromPhpImageType Determines the type of value in '$value'. When '$fromPhpImageType'
     *                                  evaluates TRUE '$value' is is assumed to be a PHP 'IMAGETYPE_*'
     *                                  integer constant.
     *
     * @return mixed    Returns a PHP 'IMAGETYPE_*' integer constant or a file extension string or
     *                  FALSE if the conversion fails.
     *
     *   1  IMAGETYPE_GIF
     *   2  IMAGETYPE_JPEG
     *   3  IMAGETYPE_PNG
     *   4  IMAGETYPE_SWF
     *   5  IMAGETYPE_PSD
     *   6  IMAGETYPE_BMP
     *   7  IMAGETYPE_TIFF_II
     *   8  IMAGETYPE_TIFF_MM
     *   9  IMAGETYPE_JPC
     *   10 IMAGETYPE_JP2
     *   11 IMAGETYPE_JPX
     *   12 IMAGETYPE_JB2
     *   13 IMAGETYPE_SWC
     *   14 IMAGETYPE_IFF
     *   15 IMAGETYPE_WBMP
     *   16 IMAGETYPE_XBM
     *   256 IMAGETYPE_PCC_GD2
     */
    public static function getImageType($value, $fromPhpImageType = true) {
        if($fromPhpImageType) {
            if(!is_numeric($value)) {
                return false;
            }
            if(function_exists("image_type_to_extension")) {
                $ext = image_type_to_extension(intval($value), false);
                if(empty($ext)) {
                    return false;
                }
                if(strlen($ext) > 3 && "jpeg" == strtolower($ext)) {
                    return "jpg";
                }
                return $ext;
            }
        }
        static $_extns;
        if(!isset($_extns)) {
            $_extns = array(
                IMAGETYPE_GIF => "gif",
                IMAGETYPE_JPEG => "jpg",
                IMAGETYPE_JPC => "jpc",
                IMAGETYPE_PNG => "png",
                IMAGETYPE_SWF => "swf",
                IMAGETYPE_PSD => "psd",
                IMAGETYPE_BMP => "bmp",
                IMAGETYPE_WBMP => "bmp",
                IMAGETYPE_XBM => "xbm",
                IMAGETYPE_TIFF_II => "tif",
                IMAGETYPE_TIFF_MM => "tif",
                IMAGETYPE_IFF => "iff",
                IMAGETYPE_JB2 => "jb2",
                IMAGETYPE_JP2 => "jp2",
                IMAGETYPE_JPX => "jpx",
                IMAGETYPE_SWC => "swf");
            if(defined("IMAGETYPE_ICO")) {
                // PHP version >= 5.3
                $_extns[IMAGETYPE_ICO] = "ico";
            }
        }
        if($fromPhpImageType) {
            $value = intval($value);
            return isset($_extns[$value]) ? $_extns[$value] : false;
        }

        $value = is_string($value) ? trim($value) : "";
        if(!strlen($value)) {
            return false;
        }
        $value = strtolower(FileSystem::fileExtDot($value, true));
        if(strlen($value) > 3) {
            if(!strcmp($value, "jpeg")) {
                $value = "jpg";
            }
            else if(!strcmp($value, "tiff")) {
                $value = "tif";
            }
            else if(!strcmp($value, "wbmp")) {
                $value = "bmp";
            }
        }
        return array_search($value, $_extns);
    }
}
