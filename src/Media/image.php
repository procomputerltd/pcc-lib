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
    Description : Image manipulation class used to:
                   o Resize images and create thumbnails for example.
                   o Overlay images with watermark transparency and/or rotation.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\FileSystem;
use Procomputer\Pcclib\Error;
use Procomputer\Pcclib\Arrays;
use Procomputer\Pcclib\PhpErrorHandler;

/**
 * Image manipulation class used to resize images and overlay images with watermark transparency and/or rotation.
 */
class Image Extends Common {

    /**
     * Image file or GD resource
     * @var mixed
     */
    protected $_image = null;

    /**
     * Image properties storage. See:
     * @var array
     */
    protected $_imageProperties = null;

    /**
     * Switch: what to do when a non-critical error occurrs. (NOTE: critical errors always throw exception.)
     *  TRUE  - throw exception
     *  FALSE - return an Error object
     *         
     * @var boolean
     */
    private static $_throwErrors = true;
    
    /**
     * PHP 'IMAGETYPE_*' image type specifier.
     * @var int
     * @see http://us.php.net/manual/en/function.exif-imagetype.php
     */
    protected $_phpType = MediaConst::IMAGETYPE_UNKNOWN;

    /**
     * The following are options supported by the 'options' parameter of 'saveAs()' method.
     * 
     * alignment         int              A 'MediaConst::ALIGN_*' constant that specifies how the image is aligned in the space.
     * basename          string           Descriptive name for 'image' above when 'image' is a temporary file like 'pccAn1e.tmp'
     * height            int              Image width. Specify -1 or null for proportional.
     * imageFilter       array            A GD IMAGE_FILTER array.
     * interlace         boolean          Whether to implement interlacing.
     * options           int              One or more 'IMG_OPTION_*' constants OR'd together.
     * phpType           int              A PHP 'IMAGETYPE_*' image type specifier.
     * quality           int              JPEG image quality. Specify 0 (worst quality, smaller file) to 100 (best quality, biggest file). Default is 75.
     * sizing            int              A 'MediaConst::SIZE_*' constant that specifies how the image is sized.
     * width             int              Image width. Specify -1 or null for proportional.
     * overlayFile       string           Overlay image file.
     * overlaymergepct   int              Overlay opacity setting.
     * overlayPosition   int              A 'MediaConst::ALIGN_*' constant that specifies how the image is aligned in the space.
     * overlayRotate     int              Overlay rotation.
     * overlaytranscolor int              Overlay transparent color.
     */
    protected $_defaultOptions = array(
        'alignment'         => MediaConst::ALIGN_NONE,
        'basename'          => '',
        'height'            => null,
        'imagefilter'       => null,
        'interlace'         => null,
        'options'           => 0,
        'phptype'           => MediaConst::IMAGETYPE_UNKNOWN,
        'quality'           => MediaConst::QUALITY_DEFAULT,
        'sizing'            => MediaConst::SIZE_ZOOM,
        'width'             => null,
        'overlayfile'       => null,
        'overlaymergepct'   => null, // Overlay merge percentage 0-100%. 0 does NOTHING while 100 overlays the file AS-IS; no transparency.
        'overlayalign'      => null,
        'overlayrotate'     => null,
        'overlaytranscolor' => null
        );

    /**
     * Constructor
     *
     * @param string $image (optional) Source image file.
     */
    public function __construct($image = null) {
        if(null !== $image) {
            $this->loadImage($image);
        }
    }

    /**
     * Loads an image file or GD resource.
     * 
     * @param string|resource  $image Image file.
     * 
     * @return $this
     * 
     * @throws Exception\InvalidArgumentException
     */
    public function loadImage($image) {
        if(! is_string($image) || Types::isBlank($image)) {
            // invalid '%s' parameter '%s'
            // invalid source file parameter
            $msg = sprintf(MediaConst::T_PARAMETER_INVALID, 'image', Types::getVartype($image));
            throw new Exception\InvalidArgumentException($msg . ": expecting an image file", MediaConst::E_BAD_SOURCE_FILE_PARAM);
        }
        $imgPropertiesObj = new ImageProperties();
        try {
            $properties = $imgPropertiesObj($image);
            if($properties['errno']) {
                $code = $properties['errno'];
                $errorMsg = $properties['error'];
            }
        } catch(\Throwable $ex) {
            $code = $ex->getCode();
            $errorMsg = $ex->getMessage();
        }
        if(isset($code)) {
            throw new Exception\InvalidArgumentException($errorMsg, $code);
        }
        
        $this->_imageProperties = $properties;
        $this->_image = $this->_imageProperties['filename'];
        
        return $this;
    }

    /**
     * Sets the throw errors setting that determines whether an exception is thrown on severe 
     * errors or an Error object is returned on severe errors.
     * @param boolean $throw (optional) Sets the throw errors setting. If null the setting is not changed.
     * @return boolean Returns the previous throw errors setting..
     */
    public static function throwErrors($throw = null) {
        $return = self::$_throwErrors;
        if(null !== $throw) {
            self::$_throwErrors = (bool)$throw;
        }
        return $return;
    }
    
    /**
     * Returns currently loaded image properties.
     * @return array|null
     */
    public function getProperties() {
        return $this->_imageProperties;
    }

    /**
     * Saves image to another file with, optionally, different size and attributes.
     *
     * @param string $file      Destination filename.
     * @param array  $options   (optional) Property options.
     *
     * @return boolean|string|Error  Returns saved image file or FALSE on error.
     *
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * 
     * @see /manual/en/function.exif-imagetype.php
     * 
     */
    public function saveAs($file, $options = null) {

        if(empty($this->_image) || empty($this->_imageProperties)) {
            // the property that specifies the source image is empty. Specify in constructor or using loadImage(\$image) method
            $msg = MediaConst::T_NOT_LOADED;
            if(! self::throwErrors()) {
                // E_NOT_LOADED = no file is loaded.
                return new Error($msg, MediaConst::E_NOT_LOADED);
            }
            throw new Exception\RuntimeException($msg, MediaConst::E_NOT_LOADED);
        }
        
        if(!is_string($file) || Types::isBlank($file)) {
            $var = Types::getVartype($file);
            // T_PARAMETER_INVALID = "invalid '%s' parameter '%s'";
            $msg = sprintf(MediaConst::T_PARAMETER_INVALID, 'destFile', $var, "expecting destination file");
            if(! self::throwErrors()) {
                return new Error($msg, MediaConst::E_BAD_DEST_FILE_PARAM);
            }
            throw new Exception\InvalidArgumentException($msg, MediaConst::E_BAD_DEST_FILE_PARAM);
        }

        $destFile = trim($file);
        
        $lcTemp = array_change_key_case(Arrays::toArray($options, []));
        $lcDefaults = $this->_defaultOptions;
        $imageOptions = Arrays::extend($lcDefaults, $lcTemp);

        $imageProperties = $this->_imageProperties;
        $imageProperties['options'] = $imageOptions;

        // Make copy of source file.
        $filePath = $this->_image;

        $fromPhpType = $imageProperties['type'];

        $optionFlags = (int)$imageOptions['options'];
        
        // If data in the file is not type IMAGETYPE_PCC_GD2 save the file as-is if 'type' is the
        // same as the original type and no size, quality nor alignment parameters are specified.
        if(file_exists($destFile)) {
            /* getImageProperties() returns an array similar to:
                [filename]           = File path.
                [file_ext]           = File extension without dot.
                [width]              = Image pixel width.
                [height]             = Image pixel height.
                [type]               = A PHP 'IMAGETYPE_*' image type specifier.
                [htmlSizeAttributes] = HTML <IMG%gt; tag string like 'width="1024" height="768"'
                [mime]               = Mime-type like 'image/jp2' and 'image/png'
                [channels]           = '3' for RGB pictures and '4' for CMYK pictures
                [bits]               = The number of bits for each color.
                [errno]              = Error code number.
                [error]              = Error message.
                [throw]              = Indicates the error is a critical error that should be thrown.</pre>
             */
            if(! is_file($destFile)) { 
                $code = MediaConst::E_BAD_DEST_FILE_PARAM;
                $var = Types::getVartype($destFile);
                //  the value specified in the destination file parameter is not a file
                $msg = MediaConst::T_BAD_DEST_FILE . ": {$var}";
                if(! self::throwErrors()) {
                    return new Error($msg, $code);
                }
                throw new Exception\InvalidArgumentException($msg, $code);
            }
            $obj = new ImageProperties();
            $destProperties = $obj($destFile);
            $identical = $this->_identicalProperties($destProperties, $imageProperties);
        }
        else {
            $identical = false;
        }
        if($identical) {
            if($optionFlags & MediaConst::IMG_OPTION_ADD_FILE_EXTENSION) {
                // Add a file extension ONLY if the file has no extension.
                $path = $this->_addImageTypeFileExtension($destFile, $fromPhpType);
            }
            else {
                $path = $destFile;
            }

            $destPath = $this->_checkFileOverwriteRename($path, $optionFlags);
            if(false === $destPath) {
                $var = Types::getVartype($path);
                // T_FILE_OVERWRITE_DENIED = file exists and 'overwrite' parameter is 'false'. The file is not overwritten
                // E_FILE_EXISTS = 0x028F; // file exists and overite not allowed.
                $msg = MediaConst::T_FILE_OVERWRITE_DENIED . ": {$var}";
                $code = MediaConst::E_FILE_EXISTS;
                if(! self::throwErrors()) {
                    return new Error($msg, $code);
                }
                throw new Exception\InvalidArgumentException($msg, $code);
            }

            try {
                $res = FileSystem::copyFile($filePath, $destPath);
                if(! $res) {
                    $code = MediaConst::E_UNSPECIFIED;
                    $msg = 'Copy file failed: zero bytes are copied';
                }
            }
            catch(\Exception $e) {
                $code = $e->getCode();
                $msg = $e->getMessage();
                $res = false;
            }
            if(! $res) {
                if(! self::throwErrors()) {
                    return new Error($msg, $code);
                }
                throw new Exception\InvalidArgumentException($msg, $code);
            }
            return $destPath;
        }

        /*  ImageResize() returns dimensions array:
          [dstX] => Destination X coordinate
          [dstY] => Destination Y coordinate
          [dstW] => Destination Width
          [dstH] => Destination Height
          [srcX] => Source X coordinate
          [srcY] => Source Y coordinate
          [srcW] => Source Width
          [srcH] => Source Height
         */
        $resizer = new ImageResize();
        // __invoke($srcW = null, $srcH = null, $dstW = null, $dstH = null, $sizing = null, $alignment = null)
        $imgdata = $resizer(
            $imageProperties['width'],
            $imageProperties['height'],
            $imageOptions['width'],
            $imageOptions['height'],
            $imageOptions['sizing'],
            $imageOptions['alignment']
            );
        if(false === $imgdata) {
            return false;
        }

        $importer = new ImportImage();
        $srcImg = $importer->import($filePath, $fromPhpType, false);
        if(false === $srcImg) {
            $msg = $importer->lastErrorMsg;
            $code = $importer->lastErrorCode;
            if(! self::throwErrors()) {
                return new Error($msg, $code);
            }
            throw new Exception\InvalidArgumentException($msg, $code);
        }

        $phpErrorHandler = new PhpErrorHandler();
        $image = $phpErrorHandler->call(function()use($imgdata){
            return imagecreatetruecolor($imgdata['dstW'], $imgdata['dstH']);
        });
        if(false === $image) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $default = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecreatetruecolor");
            $msg = $phpErrorHandler->getErrorMsg($default, "cannot create image");
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            if(! self::throwErrors()) {
                return new Error($msg, $code);
            }
            throw new Exception\RuntimeException($msg, $code);
        }
        
        $dstImg = $phpErrorHandler->call(function()use($imgdata){
            return imagecreatetruecolor($imgdata['dstW'], $imgdata['dstH']);
        });
        if(false === $dstImg) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $default = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecreatetruecolor");
            $msg = $phpErrorHandler->getErrorMsg($default, "cannot create image");
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            if(! self::throwErrors()) {
                return new Error($msg, $code);
            }
            throw new Exception\RuntimeException($msg, $code);
        }

        $res = $phpErrorHandler->call(function()use($dstImg, $srcImg, $imgdata){
            return imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $imgdata['dstW'], $imgdata['dstH'], $imgdata['srcW'], $imgdata['srcH']);
        });
        if(!$res) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $default = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecopyresampled");
            $msg = $phpErrorHandler->getErrorMsg($default, "cannot copy image");
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            if(! self::throwErrors()) {
                return new Error($msg, $code);
            }
            throw new Exception\RuntimeException($msg, $code);
        }

        $haveOverlayFile = ! Types::isBlank($imageOptions['overlayfile']);
        
        if($haveOverlayFile && ($optionFlags & MediaConst::IMG_OPTION_OVERLAY_BEFORE_FILTER)) {
            $this->_overlay($dstImg, $imageOptions);
            $overlay = true;
        }
        else {
            $overlay = false;
        }

        $imageFilter = $imageOptions['imagefilter'];
        if(is_array($imageFilter) && ! empty($imageFilter)) {
            $filterer = new ImageFilter();
            if(false === $filterer->applyFilter($dstImg, $imageFilter)) {
                return false;
            }
        }

        if(! $overlay && $haveOverlayFile && ($optionFlags & MediaConst::IMG_OPTION_OVERLAY_AFTER_FILTER)) {
            $this->_overlay($dstImg, $imageOptions);
        }

        $phpType = $imageOptions['phptype'];
        if(empty($phpType) || MediaConst::IMAGETYPE_UNKNOWN == $phpType) {
            $phpType = $fromPhpType;
        }

        if($optionFlags & MediaConst::IMG_OPTION_ADD_FILE_EXTENSION) {
            // Add a file extension ONLY if the file has no extension.
            $destFile = $this->_addImageTypeFileExtension($destFile, $phpType);
        }

        $destPath = $this->_checkFileOverwriteRename($destFile, $optionFlags);
        if(false === $destPath) {
            // E_FILE_EXISTS = 0x028F; // file exists and overite not allowed.
            // T_FILE_OVERWRITE_DENIED = "file exists and 'overwrite' parameter is 'false'. The file is not overwritten";
            $msg = MediaConst::T_FILE_OVERWRITE_DENIED . ': ' . Types::getVartype($destFile);
            if(! self::throwErrors()) {
                return new Error($msg, MediaConst::E_FILE_EXISTS);
            }
            throw new Exception\RuntimeException($msg, MediaConst::E_FILE_EXISTS);
        }

        // _invoke($imgResource, $destFile, $phpType, $quality, $interlace = 0, $throw = true)
        $exporter = new ExportImage();
        $returnFile = $exporter->export($dstImg, $destPath, $phpType, $imageOptions['quality'], 
            $imageOptions['interlace'], self::$_throwErrors);
        
        return $returnFile;
    }

    /**
     * Overlays an image on another image. Option 'overlaymergepct' specifies merge percentage transparency (0-100)
     * @param resource $dstImg  The image on which the overlay is applied.
     * @param array    $options The overlay option values. See descriptions above
     * @return boolean
     */
    protected function _overlay($dstImg, array $options) {
        $overlay = new Overlay();
        $res = $overlay->overlay(
            $dstImg, 
            $options['overlayfile'],
            $options['overlaymergepct'],
            (int)$options['options'],
            $options['overlayalign'],
            $options['overlayrotate'],
            $options['overlaytranscolor']
            );
        return $res;
    }

    /**
     * Determines whether the new, saved image will be identical to the source, that is, no property option
     * would cause the new image to be different therefore requiring sizing, zooming, filtereing etc.
     * @return boolean
     */
    protected function _identicalProperties($destImageProperties, $sourceImageProperties) {
        /* getProperties() returns an array similar to:
            [filename]           = File path.
            [file_ext]           = File extension without dot.
            [width]              = Image pixel width.
            [height]             = Image pixel height.
            [type]               = A PHP 'IMAGETYPE_*' image type specifier.
            [htmlSizeAttributes] = HTML <IMG%gt; tag string like 'width="1024" height="768"'
            [mime]               = Mime-type like 'image/jp2' and 'image/png'
            [channels]           = '3' for RGB pictures and '4' for CMYK pictures
            [bits]               = The number of bits for each color.
            [errno]              = Error code number.
            [error]              = Error message.
            [throw]              = Indicates the error is a critical error that should be thrown.</pre>
         */
        $options = $sourceImageProperties['options'];
        
        $width = $sourceImageProperties['width'];
        if(-1 !== $width && $width !== $destImageProperties['width']) {
            return false;
        }
        $height = $sourceImageProperties['height'];
        if(-1 !== $height && $height !== $destImageProperties['height']) {
            return false;
        }

        $quality = $options['quality'];
        if(-1 !== $quality && MediaConst::QUALITY_DEFAULT !== $quality) {
            return false;
        }

        if(MediaConst::ALIGN_NONE !== $options['alignment']) {
            return false;
        }

        $phpType = $options['phpType'];
        $srcPhpType = $destImageProperties['type'];
        if(MediaConst::IMAGETYPE_PCC_GD2 !== $srcPhpType && -1 !== $phpType
            && MediaConst::IMAGETYPE_UNKNOWN !== $phpType && $phpType !== $srcPhpType) {
            return false;
        }

        $imageFilter = $options['imageFilter'];
        if(is_array($imageFilter) && ! empty($imageFilter)) {
            return false;
        }

        if($options['options'] & (MediaConst::IMG_OPTION_OVERLAY_BEFORE_FILTER | MediaConst::IMG_OPTION_OVERLAY_AFTER_FILTER)) {
            return false;
        }

        return true;
    }

    /**
     * Appends a file extension to a file path that relates to the image type.
     *
     * @param string $path      The path/filename for which to add an image file extension.
     *
     * @param int    $phpType   (optional) A PHP 'IMAGETYPE_*' image type specifier.Unspecified uses getParameter('phpType')
     *
     * @see http://us3.php.net/manual/en/function.exif-imagetype.php
     *
     * @return string Return the file/pathname with the new extension appended unless the original
     * extension is same as new extension.
     */
    protected function _addImageTypeFileExtension($path, $phpType = null) {
        if(! is_string($path) || ! strlen(trim($path))) {
            return $path;
        }
        if(is_null($phpType)) {
            $phpType = $this->getParameter('phpType');
        }
        $ext = ImageType::getImageType($phpType, true);
        if(false !== $ext) {
            /* PHP's pathinfo(path) returns an array:
                [dirname]   => c:\temp
                [basename]  => base.foo.bar
                [extension] => bar
                [filename]  => base.foo
             */
            $info = pathinfo($path);
            if(! empty($info['extension']) && $phpType === ImageType::getImageType($info['extension'], false)) {
                // Remove the extension.
                $info['basename'] = $info['filename'];
            }
            $path = FileSystem::joinPath('/', $info['dirname'], $info['basename'] . FileSystem::fileExtDot($ext));
        }
        return $path;
    }

    /**
     * If the file does not exist simply returns the full path/filename. Otherwise, depending on the 'flags'
     * parameter, either a new, unique, filename is returned or an FALSE is returned.
     *
     * @param string $file     The path/filename.
     * @param int    $options  One or more OR'd 'IMG_OPTION_*' constants that determines action to take when the file exists.
     *
     * @return string|boolean Returns a path/filename or FALSE on error.
     */
    protected function _checkFileOverwriteRename($file, $options) {
        if(! file_exists($file)) {
            return $file;
        }
        if(! is_file($file)) {
            return false;
        }
        if($options & MediaConst::IMG_OPTION_RENAME) {
            $file = FileSystem::getUniqueFilename($file);
            return $file;
        }
        return ($options & MediaConst::IMG_OPTION_OVERWRITE) ? $file : false;
    }
    
    /**
     * Returns the list of available options and their default values.
     * @return array
     */
    public function getDefaultOptions() {
        return $this->_defaultOptions;
    }
}
