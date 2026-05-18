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
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\FileSystem;
use Procomputer\Pcclib\Media\Exception\RuntimeException;
use Procomputer\Pcclib\Media\Exception\InvalidArgumentException;
use Procomputer\Pcclib\Media\MemoryLog;

/**
 * Image manipulation class used to resize images and overlay images with watermark transparency and/or rotation.
 */
/**
 * @method string getAlignment(int $arg)
 * @method string getBasename(string $arg)
 * @method string getHeight(int $arg)
 * @method string getImagefilter(string $arg)
 * @method string getInterlace(string $arg)
 * @method string getOptions(array $arg)
 * @method string getOverlayalign(int $arg)
 * @method string getOverlayfile(string $arg)
 * @method string getOverlaymergepct(int $arg)
 * @method string getOverlayrotate(int $arg)
 * @method string getOverlaytranscolor(int $arg)
 * @method string getPhptype(int $arg)
 * @method string getQuality(int $arg)
 * @method string getSizing(int $arg)
 * @method string getWidth(int $arg)
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
     * interlace         bool             Whether to implement interlacing.
     * options           int              One or more 'IMG_OPTION_*' constants OR'd together.
     * phpType           int              A PHP 'IMAGETYPE_*' image type specifier.
     * quality           int              JPEG image quality and PNG compression level. Specify 0 (worst quality, smaller file) to 100 (best quality, biggest file). Default is 75.
     * sizing            int              A 'MediaConst::SIZE_*' constant that specifies how the image is sized.
     * width             int              Image width. Specify -1 or null for proportional.
     * overlayFile       string           Overlay image file.
     * overlaymergepct   int              Overlay opacity setting.
     * overlayPosition   int              A 'MediaConst::ALIGN_*' constant that specifies how the image is aligned in the space.
     * overlayRotate     int              Overlay rotation.
     * overlaytranscolor int              Overlay transparent color.
     */
    protected $alignment = MediaConst::ALIGN_NONE;
    protected $basename = '';
    protected $height = null;
    protected $imagefilter = null;
    protected $interlace = null;
    protected $options = 0;
    protected $overlayalign = 0;
    protected $overlayfile = null;
    protected $overlaymergepct = 0; // Overlay merge percentage 0-100%. 0 does NOTHING while 100 overlays the file AS-IS; no transparency.
    protected $overlayrotate = 0;
    protected $overlaytranscolor = null;
    protected $phptype = MediaConst::IMAGETYPE_UNKNOWN;
    protected $quality = MediaConst::QUALITY_DEFAULT;
    protected $sizing = MediaConst::SIZE_ZOOM;
    protected $width = null;

    /**
     * Constructor
     *
     * @param array $options (optional) options.
     */
    public function __construct(array $options = []) {
        parent::__construct($options);
        if(isset($options['image']) && ! Types::isBlank($options['image'])) {
            $this->loadImage($options['image']);
        }
    }

    /**
     * Set an option
     * @param string $name
     * @param mixed  $args
     * @return $this
     * @throws RuntimeException
     */
    public function __call(string $name, mixed $args): mixed {
        $l = strlen($name);
        if($l > 3) {
            $action = substr($name, 0, 3);
            $set = 'set' === $action ;
            if($set || ('get' === $action)) {
                $property = strtolower(substr($name, 3));
                if(property_exists($this, $property)) {
                    if($set) {
                        $this->$property = (is_array($args) && count($args)) ? reset($args) : $args;
                        return $this;
                    }
                    return $this->$property;
                }
            }
        }
        throw new RuntimeException("In " . __CLASS__ . "::__call(): method not found: '{$name}'");
    }

    /**
     * Sets a property value.
     * @param string $key
     * @param mixed $val
     * @return $this
     * @throws RuntimeException
     */
    public function __set(string $key, mixed $val) {
        if(property_exists($this, $key)) {
            $this->$key = $val;
            return $this;
        }
        $var = Types::getVarType($key);
        trigger_error("Undefined property: " . get_class($this) . "::\${$var}", E_USER_WARNING);
        return null;
    }

    /**
     * Returns a property value.
     * @param string $key
     * @return mixed
     * @throws RuntimeException
     */
    public function __get(string $key) : mixed {
        if(property_exists($this, $key)) {
            return $this->$key;
        }
        $var = Types::getVarType($key);
        trigger_error("Undefined property: " . get_class($this) . "::\${$var}", E_USER_WARNING);
        return null;
    }

    /**
     * Loads an image file or GD resource.
     *
     * @param string|resource  $image Image file.
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function loadImage(string $image) {
        if(Types::isBlank($image)) {
            // invalid '%s' parameter '%s'
            // invalid source file parameter
            $msg = sprintf(MediaConst::T_PARAMETER_INVALID, 'image', Types::getVartype($image));
            throw new InvalidArgumentException($msg . ": expecting an image file", MediaConst::E_BAD_SOURCE_FILE_PARAM);
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
            throw new InvalidArgumentException($errorMsg, $code);
        }

        $this->_imageProperties = $properties;
        $this->_image = $this->_imageProperties['filename'];

        return $this;
    }

    /**
     * Returns currently loaded image properties.
     * @return array|null
     */
    public function getProperties() {
        return $this->_imageProperties;
    }

    public function getProperty($key, $default = null) {
        return $this->_imageProperties[$key] ?? $default;
    }

    public function setProperty($key, $value) {
        $this->_imageProperties[$key] = $value;
        return $this;
    }

    /**
     * Saves image to another file with, optionally, different size and attributes.
     *
     * @param string $file      Destination filename.
     *
     * @return string Returns saved image file.
     * @throws RuntimeException
     * @throws InvalidArgumentException
     *
     * @see /manual/en/function.exif-imagetype.php
     *
     */
    public function saveAs(string $file) {
        
        if(empty($this->_image) || empty($this->_imageProperties)) {
            // the property that specifies the source image is empty. Specify in constructor or using loadImage(\$image) method
            $msg = MediaConst::T_NOT_LOADED;
            throw new RuntimeException($msg, MediaConst::E_NOT_LOADED);
        }

        if(Types::isBlank($file)) {
            $var = Types::getVartype($file);
            // T_PARAMETER_INVALID = "invalid '%s' parameter '%s'";
            $msg = sprintf(MediaConst::T_PARAMETER_INVALID, 'destFile', $var, "expecting destination file");
            throw new InvalidArgumentException($msg, MediaConst::E_BAD_DEST_FILE_PARAM);
        }
        /* _imageProperties initialized by _loadImage() is an array similar to:
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
        $fromPhpType = $this->_imageProperties['type'];

        $destFile = trim($file, " \t");

        // Make copy of source file.
        $sourceFile = $this->_image;

        if($this->_logging) {
            MemoryLog::$name = basename($destFile);
        }
        // If data in the file is not type IMAGETYPE_PCC_GD2 save the file as-is if 'type' is the
        // same as the original type and no size, quality nor alignment parameters are specified.
        if(file_exists($destFile)) {
            if(! is_file($destFile)) {
                $code = MediaConst::E_BAD_DEST_FILE_PARAM;
                $var = Types::getVartype($destFile);
                //  the value specified in the destination file parameter is not a file
                $msg = MediaConst::T_BAD_DEST_FILE . ": {$var}";
                throw new InvalidArgumentException($msg, $code);
            }
            $identical = false;
            if(filesize($destFile)) {
                $obj = new ImageProperties();
                try {
                    $identical = $this->_identicalProperties($obj($destFile));
                } catch (\Throwable $exc) {
                }
            }
        }
        else {
            $identical = false;
        }
        if($identical) {
            if(! ($this->options & MediaConst::IMG_OPTION_OMIT_FILE_EXTENSION)) {
                // Add a file extension ONLY if the file has no extension.
                $path = $this->_addImageTypeFileExtension($destFile, $fromPhpType, DIRECTORY_SEPARATOR);
            }
            else {
                $path = $destFile;
            }

            $destPath = $this->_checkFileOverwriteRename($path);
            if(false === $destPath) {
                $var = Types::getVartype($path);
                // T_FILE_OVERWRITE_DENIED = file exists and 'overwrite' parameter is 'false'. The file is not overwritten
                // E_FILE_EXISTS = 0x028F; // file exists and overite not allowed.
                $msg = MediaConst::T_FILE_OVERWRITE_DENIED . ": {$var}";
                $code = MediaConst::E_FILE_EXISTS;
                throw new InvalidArgumentException($msg, $code);
            }

            try {
                $res = FileSystem::copyFile($sourceFile, $destPath);
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
                throw new InvalidArgumentException($msg, $code);
            }
            return $destPath;
        }

        /*  ImageResizeAlign() returns dimensions array:
          [dstX] => Destination X coordinate
          [dstY] => Destination Y coordinate
          [dstW] => Destination Width
          [dstH] => Destination Height
          [srcX] => Source X coordinate
          [srcY] => Source Y coordinate
          [srcW] => Source Width
          [srcH] => Source Height
         */
        $resizer = new ImageResizeAlign();
        $imgdata = $resizer(
            $this->_imageProperties['width'],
            $this->_imageProperties['height'],
            $this->width,
            $this->height,
            $this->sizing,
            $this->alignment
            );
        if(false === $imgdata) {
            return false;
        }

        $importer = new ImportImage();
        if($this->_logging) {
            $log = new MemoryLog();
        }
        $srcImg = $importer->import($sourceFile, $fromPhpType);
        if($this->_logging) {
            $log->log("ImportImage::import(file)", filesize($sourceFile));
        }
        if(false === $srcImg) {
            $this->lastErrorMsg = $importer->lastErrorMsg;
            $this->lastErrorCode = $importer->lastErrorCode;
            throw new RuntimeException($this->lastErrorMsg, $this->lastErrorCode);
        }

        if($this->_logging) {
            $log = new MemoryLog();
        }
        $dstImg = $this->getGd()->imageCreateTrueColor($imgdata['dstW'], $imgdata['dstH'], true);
        if($this->_logging) {
            $log->log('Image::saveAs(imageCreateTrueColor)', $imgdata['dstW'] * $imgdata['dstH']);
            // unset($log);
        }
        
        $phpErrorHandler = new PhpErrorHandler();
        if($this->_logging) {
            $log = new MemoryLog();
        }
        $res = $phpErrorHandler->call(function()use($dstImg, $srcImg, $imgdata){
            return imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $imgdata['dstW'], $imgdata['dstH'], $imgdata['srcW'], $imgdata['srcH']);
        });
        if($this->_logging) {
            $log->log('Image::saveAs(imagecopyresampled)', $imgdata['dstW'] * $imgdata['dstH']);
            unset($log);
        }
        
        // Free memory.
        $this->getGd()->imagedestroy($srcImg);
        
        if(!$res) {
            // T_PHP_FUNCTION_FAILED = image function '%s' failed
            $default = sprintf(MediaConst::T_PHP_FUNCTION_FAILED, "imagecopyresampled");
            $msg = $phpErrorHandler->getErrorMsg($default, "cannot copy image");
            // a PHP image function has failed
            $code = MediaConst::E_PHP_FUNCTION_FAILED;
            throw new RuntimeException($msg, $code);
        }

        $haveOverlayFile = ! Types::isBlank($this->overlayfile);
        if($haveOverlayFile && ($this->options & MediaConst::IMG_OPTION_OVERLAY_BEFORE_FILTER)) {
            $this->_overlay($dstImg);
            $overlay = true;
        }
        else {
            $overlay = false;
        }

        if(is_array($this->imagefilter) && ! empty($this->imagefilter)) {
            $filterer = new ImageFilter();
            if(false === $filterer->applyFilter($dstImg, $this->imagefilter)) {
                return false;
            }
        }

        if(! $overlay && $haveOverlayFile) {
            $this->_overlay($dstImg);
        }

        $phpType = $this->phptype;
        if(empty($phpType) || MediaConst::IMAGETYPE_UNKNOWN == $phpType) {
            $phpType = $fromPhpType;
        }

        if(!($this->options & MediaConst::IMG_OPTION_OMIT_FILE_EXTENSION)) {
            // Add a file extension ONLY if the file has no extension.
            $destFile = $this->_addImageTypeFileExtension($destFile, $phpType, DIRECTORY_SEPARATOR);
        }

        $destPath = $this->_checkFileOverwriteRename($destFile);
        if(false === $destPath) {
            // E_FILE_EXISTS = 0x028F; // file exists and overite not allowed.
            // T_FILE_OVERWRITE_DENIED = "file exists and 'overwrite' parameter is 'false'. The file is not overwritten";
            $msg = MediaConst::T_FILE_OVERWRITE_DENIED . ': ' . Types::getVartype($destFile);
            throw new RuntimeException($msg, MediaConst::E_FILE_EXISTS);
        }

        $interlace = Types::isBool($this->interlace) ? Types::boolVal($this->interlace) : false;
        $quality = is_numeric($this->quality) ? intval($this->quality) : -1;
        // _invoke($imgResource, $destFile, $phpType, $quality, $interlace = 0, $throw = true)
        $exporter = new ExportImage();
        $exporter->setLogging($this->getLogging());
        $returnFile = $exporter->export($dstImg, $destPath, $phpType, $quality, $interlace);

        return $returnFile;
    }

    /**
     * Overlays an image on another image. Option 'overlaymergepct' specifies merge percentage transparency (0-100)
     * @param resource $dstImg  The image on which the overlay is applied.
     * @return boolean
     */
    protected function _overlay($dstImg) {
        $overlay = new Overlay();
        $overlay->setLogging($this->getLogging());
        try {
            $res = $overlay->overlay(
                $dstImg,
                $this->overlayfile,
                $this->overlaymergepct,
                (int)$this->options,
                $this->overlayalign,
                $this->overlayrotate,
                $this->overlaytranscolor,
                );
            return $res;
        } catch (\Throwable $exc) {
            $msg = "cannot overlay image: " . $exc->getMessage();
            throw new RuntimeException($msg, 0, $exc);
        }
    }

    protected function _setOptions(array $options) {
        foreach($options as $key => $val) {
            $this->$key = $val;
        }
        return $this;
    }
    
    /**
     * Determines whether the new, saved image will be identical to the source, that is, no property option
     * would cause the new image to be different therefore requiring sizing, zooming, filtereing etc.
     * @param array $destImageProperties
     * @return bool
     */
    protected function _identicalProperties(array $destImageProperties) : bool {
        /* Expected properties. These are described in class ImageProperties.
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
        $options = $this->_imageProperties['options'] ?? [];

        $width = $this->_imageProperties['width'];
        if(-1 !== $width && $width !== $destImageProperties['width']) {
            return false;
        }
        $height = $this->_imageProperties['height'];
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
    protected function _addImageTypeFileExtension($path, $phpType = null, string $dirSeparator = '') {
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
            if(! strlen($dirSeparator)) {
                $dirSeparator = '/';
                if(false === strpos($path, $dirSeparator) && false !== strpos($path, DIRECTORY_SEPARATOR)) {
                    $dirSeparator = DIRECTORY_SEPARATOR;
                }
            }
            $path = FileSystem::joinPath($dirSeparator, $info['dirname'], $info['basename'] . FileSystem::fileExtDot($ext));
        }
        return $path;
    }

    /**
     * If the file does not exist simply returns the full path/filename. Otherwise, depending on the 'flags'
     * parameter, either a new, unique, filename is returned or an FALSE is returned.
     *
     * @param string $file     The path/filename.
     *
     * @return string|boolean Returns a path/filename or FALSE on error.
     */
    protected function _checkFileOverwriteRename($file) {
        if(! file_exists($file)) {
            return $file;
        }
        if(! is_file($file)) {
            return false;
        }
        if($this->options & MediaConst::IMG_OPTION_RENAME) {
            $file = FileSystem::getUniqueFilename($file);
            return $file;
        }
        return ($this->options & MediaConst::IMG_OPTION_OVERWRITE) ? $file : false;
    }
    
}
