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
    Description : Constants used by Media classes.
*/
namespace Procomputer\Pcclib\Media;

/**
 * Constants used by Media classes.
 */
class MediaConst {

    /**
     * Options when saving a file.
     */
    const IMG_OPTION_OVERWRITE = 1; // Attempt to overwrite file if exists.
    const IMG_OPTION_RENAME = 2;    // Attempt to save file as different name if exists.
    const IMG_OPTION_ADD_FILE_EXTENSION = 4; // Automatically append the file extension matching the file type.
    const IMG_OPTION_OVERLAY_BEFORE_FILTER = 8;//When saving/resizing, merge an image over this image.
    const IMG_OPTION_OVERLAY_AFTER_FILTER = 16; // When saving/resizing, merge an image over this image.
    const IMG_OPTION_OVERLAY_SIZE_TO_FIT = 32; // Resize the overlay image if it's larger X or Y than the source image.
    const IMG_OPTION_OVERLAY_REPEAT = 64; // Repeat the overlay image across the source image.

    const IMAGETYPE_PCC_GD2 = 0x100; // A custom bit flag that indicate the stored image is GD2 IMAGETYPE_GD2 data.
    const IMAGETYPE_UNKNOWN = -1;    // An unspecified/unknown image PHP IMAGETYPE_* const.
    const QUALITY_DEFAULT = -1;      // Use default quality.
    const QUALITY_DEFAULT_JPEG = 75; // Default JPEG image quality ranges from 0 (worst quality, smaller file)
                                     // to 100 (best quality, biggest file). The default is The Independent JPEG
                                     // Group (IJG) default quality value (about 75).
    const OVERLAY_OPACITY_DEFAULT = 30; // When merging, the default opacity.

    /**
     * Graphic image sizing constants.
     */
    const SIZE_ZOOM = 1;    // Display the entire object, resizing it as necessary without distorting the image.
                            // This may result in unused areas of the destination image frame.
    const SIZE_CLIP = 2;    // Displays the object at actual size. If the object is larger than the destination
                            // image dimensions it is clipped on the right and bottom of the frame's edges.
    const SIZE_STRETCH = 3; // Size the object to fill the frame. This may distort the image.
    const SIZE_SHRINK = 4;  // Display the entire object, resizing it as necessary without distorting the image
                            // only when the object is larger than the destination image frame.

    /**
     * Graphics image alignment constants.
     */
    const ALIGN_NONE = 0; // Default. The image is aligned in the top-left the frame (same as ALIGN_TOPLEFT).
    const ALIGN_TOPLEFT = 1; // The image is aligned in the top-left the frame.
    const ALIGN_TOPCENTER = 2; // The image is aligned in the top-left the frame.
    const ALIGN_TOPRIGHT = 3; // The image is aligned in the top-right of the frame.
    const ALIGN_BOTTOMLEFT = 4; // The image is aligned in the bottom-left of the frame.
    const ALIGN_BOTTOMCENTER = 5; // The image is aligned in the top-left the frame.
    const ALIGN_BOTTOMRIGHT = 6; // The image is aligned in the bottom-right of the frame.
    const ALIGN_CENTER = 7; // The image is aligned in the center of the frame.

    /**
     * Graphics error constants.
     */
    const E_UNSPECIFIED = -1;
    const E_NO_LIBRARY = 0x0286; // the image function library is not available. Check php.ini for included image library extension
    const E_NOT_LOADED = 0x0280; // no file is loaded.
    const E_NOT_IMAGE = 0x0292; // the data is corrupted or is not a supported image format
    const E_FILE_EMPTY = 0x028B; // the file is empty
    const E_NOT_CORRECT_IMAGE_TYPE = 0x028d; // cannot create image from file as the file is the wrong type or is corrupt

    const E_BAD_DEST_FILE_PARAM = 0x0288; // invalid destination file parameter
    const E_BAD_SOURCE_FILE_PARAM = 0x0287; // invalid source file parameter
    const E_BAD_SOURCE_FILE_PROPERTY = 0x0286; // invalid source file property
    const E_BAD_TYPE = 0x0289; // invalid image type
    const E_FILE_EXISTS = 0x028F; // file exists and overite not allowed.
    const E_FILE_NOT_FOUND = 0x0281; // file not found
    const E_IMAGE_SIZE_INVALID = 0x0282; // invalid image dimensions
    const E_INVALID_ALIGN_PARAM = 0x0284; // invalid image alignment parameter
    const E_INVALID_ROTATE_PARAM = 0x0290; // invalid image rotation parameter
    const E_INVALID_SIZE_PARAM = 0x0283; // invalid image size parameter
    const E_IPTC_CANNOT_EMBED = 0x2B2; // cannot embed IPTC code
    const E_IPTC_CANNOT_OPEN = 0x2B3; // cannot open image file
    const E_IPTC_CANNOT_WRITE = 0x2B4; // cannot write IPTC code to image file
    const E_IPTC_INVALID_IPTC_FIELD = 0x2B1; // invalid IPTC embedding code
    const E_IPTC_NO_FUNCTION = 0x2b0; // IPTC functions are not enabled.
    const E_NOT_INITIALIZED = 0x028A; // no image file is loaded
    const E_NO_FUNCTION = 0x028B; // cannot create image as image function '%s' is not available
    const E_NO_OVERLAY_IMAGE = 0x028E; // no overlay image specified in the overlayImage property.
    const E_OVERLAY_TRANSPARENT = 0x028F; // overlay image transparent color cannot be set.
    const E_PARAMETER_INVALID = 0x0291; // invalid function parameter
    const E_PHP_FUNCTION_FAILED = 0x0285; // a PHP image function has failed
    const E_PROPERTY_NOT_FOUND = 0x028D; // Class property not found.
    const E_TYPE_MISMATCH = 0x028C; // Type mismatch.
    
    /**
     * Graphics text constants.
     */
    const T_BAD_IMAGE_TYPE = "not a supported image type '%s'";
    const T_BAD_DEST_FILE = "the value specified in the destination file parameter is not a file";
    const T_FILENAME_EMPTY = "the 'filename' property is empty";
    const T_FILE_EMPTY = "file '%s' is empty";
    const T_FILE_NOT_FOUND = "file not found: '%s'";
    const T_FILE_OVERWRITE_DENIED = "file exists and 'overwrite' parameter is 'false'. The file is not overwritten";
    const T_NOT_LOADED = "the property that specifies the source image is empty. Specify in constructor or using loadImage(\$image) method";
    const T_NOT_CORRECT_IMAGE_TYPE = "cannot create image as the file is not '%s' type or is corrupt";
    const T_NOT_IMAGE = "the '%s' parameter is not image or is not a supported image format: '%s'";
    const T_NOT_IMAGE_RESOURCE = "the '%s' parameter '%s' is not a GD image resource";
    const T_NOT_INITIALIZED = "no image file is loaded; the image properties are uninitialized";
    const T_NO_FUNCTION = "cannot manipulate image as image function '%s' is not available";
    const T_NO_LIBRARY = "cannot process images; the image function library is not available. Check php.ini for included image library extension";
    const T_NO_OVERLAY_IMAGE = 'Cannot overlay image: no overlay image specified.';
    const T_OVERLAY_CANNOT_SET_TRANSPARENT = 'cannot set the transparency color.';
    const T_PARAMETER_INVALID = "invalid '%s' parameter '%s'";
    const T_PHP_FUNCTION_FAILED = "image function '%s' failed";
    const T_PROPERTY_NOT_FOUND = 'property not found: %s';
    const T_SOURCE_FILE_NOT_FOUND = "source file not found: '%s'";
    const T_IMAGE_SIZE_INVALID = "invalid image width(%s) and/or height(%s) parameters";
    const T_IPTC_NO_FUNCTION = "cannot embed IPTC code: IPTC functions are not enabled";
    const T_IPTC_INVALID_IPTC_FIELD = "invalid IPTC embedding code '%s'";
    
}