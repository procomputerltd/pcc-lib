<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Nov 18, 2018, 9:11:55 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer 
*/
namespace Procomputer\Pcclib\Tests;

use Procomputer\Pcclib\Media;
use Procomputer\Pcclib\Media\MediaConst;
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\Error;

class TestImage extends TestCommon {

    /**
     * The name of this test class.
     * @var string
     */
    public $name = 'Image';
    
    /**
     * The description of this test class.
     * @var string
     */
    public $description = 'Pcc Image manipulation class tests';
        
    /**
     * Namespace to test.
     * @var string
     */
    protected $_namespace = 'Procomputer\Pcclib\Media';
    
    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions = true;
    
    /**
     * Tests Filesystem class static methods.
     * 
     * @param mixed $values  (optional) Values.
     * @param mixed $options (optional) Options. 
     * 
     * @return array
     */
    public function exec($values = null, $options = null) {

        $valuesArray = (null === $values) ? [] : (array)$values;
        
        $sourceImage = isset($valuesArray['sourceImage']) ? $valuesArray['sourceImage'] : null;
        $destImage = isset($valuesArray['destImage']) ? $valuesArray['destImage'] : null;
        $mediaImage = new Media\Image();
        
        try {
            $mediaImage->loadImage($sourceImage);
            $error = null;
        } catch (\Throwable $ex) {
            $error = new Error($ex);
        }
        if(null !== $error) {
            $var = Types::getVartype($sourceImage);
            $result = "Class::method 'Procomputer\Pcclib\Media\Image::loadImage()' threw Exception: \$sourceImage parameter is '{$var}'";
            $resultType = 'ERROR';
            $success = false;
            $throwsExceptions = true; 
            $threwEx = true;
        }
        else {
            try {
                $result = $mediaImage->saveAs($destImage, $options);
                $error = null;
            } catch (\Throwable $ex) {
                $error = new Error($ex);
            }
            if(null !== $error) {
                $success = false;
                $result = "Class::method 'Procomputer\Pcclib\Media\Image::saveAs()' threw Exception:";
                $resultType = 'ERROR';
                $throwsExceptions = true; 
                $threwEx = true;
            }
            else {
                $success = $throwsExceptions = $threwEx = false;
                if(is_string($result)) {
                    if(file_exists($result)) {
                        $success = true;
                    }
                    else {
                        $var = Types::getVartype($result);
                        $error = new Error("Error: saveAs() method returned the name of an image file that does not exist: '{$var}'");
                    }
                }
                else {
                    if(is_object($result) && $result instanceof Error) {
                        $error = $result;
                    }
                    else {
                        $var = Types::getVartype($result);
                        $error = new Error("Error: Image::saveAs() method returned a value whose type is unrecognized: '{$var}'");
                    }
                }
                $resultType = 'image';
            }
        }
        
        $optionsTable = $this->_buildOptionsTable($mediaImage, $options);
        
        // __construct($classpath, $method, $params, $success, $results, Pcclib\Error $error = null, $throwsExceptions = false, $threw = false)            
        $this->_testResults[] = new TestResult(
            'Procomputer\Pcclib\Media\Image', 
            'saveAs', 
            [
                'sourceImage' => $sourceImage, 
                'destImage' => $destImage, 
                'options' => chr(1) . 'text/html' . chr(1) . $optionsTable
            ], 
            $success, 
            $result, 
            $resultType, 
            $error, 
            $throwsExceptions, 
            $threwEx);
        return $this->_testResults;
    }

    /**
     * 
     * @param \Procomputer\Pcclib\Media\Image $mediaImage
     * @param array $options
     * @return string Returns HTML table script.
     */
    protected function _buildOptionsTable(Media\Image $mediaImage, $options) {
        
        $defaults = $mediaImage->getDefaultOptions();
        $rows = ['<table>'];
        $rows[] = '    <tr><th>OPTION NAME</th><th>SETTING</th><th>VALUE</th></tr>';
        $template = '    <tr><td>%s</td><td>%s</td><td>%s</td></tr>';
        foreach($defaults as $key => $val) {
            if(isset($options[$key])) {
                $val = $options[$key];
            }
            $valType = Types::getVartype($val);
            $desc = '';
            switch($key){
            case 'alignment': //MediaConst::ALIGN_NONE,
            case 'overlayalign': //null,
                $desc = $this->_getAlignmentDescription($val);
                break;
            case 'sizing': //MediaConst::SIZE_ZOOM,
                $desc = $this->_getSizingDescription($val);
                break;
            case 'phptype': //MediaConst::IMAGETYPE_UNKNOWN,
                $desc = $this->_getImageTypeDescription($val);
                break;
            case 'options':
                $desc = $this->_getOptionsDescription($val);
                break;
            default:
                //case 'basename':          //'',
                //case 'height':            //null,
                //case 'imagefilter':       //null,
                //case 'interlace':         //null,
                //case 'options':           //0,
                //case 'quality':           //MediaConst::QUALITY_DEFAULT,
                //case 'width':             //null,
                //case 'overlayfile':       //null,
                //case 'overlaymergepct':   //null, // Overlay merge percentage 0-100%. 0 does NOTHING while 100 overlays the file AS-IS; no transparency.
                //case 'overlayrotate':     //null,
                //case 'overlaytranscolor': //null
                if(null === $val) {
                    $desc = 'NULL';
                }
                else {
                    $desc = $valType;
                }
            }
            $eKey = htmlentities($key);
            $eValue = htmlentities($valType);
            $eDesc = htmlentities($desc);
            if(is_null($val) || $eValue === $eDesc) {
                $eValue = '';
            }
            $rows[] = sprintf($template, $eKey, $eDesc, $eValue);
        }
        $rows[] = '</table>';
        $table = implode(PHP_EOL, $rows);
        return $table;
    }
    
    /**
     * Returns a string description for an alignment constant.
     * @param int $align An 'ALIGN_*' constant
     * @return string
     */
    protected function _getAlignmentDescription($align) {
        $table = [
            MediaConst::ALIGN_NONE => 'ALIGN_NONE',
            MediaConst::ALIGN_TOPLEFT => 'ALIGN_TOPLEFT',
            MediaConst::ALIGN_TOPCENTER => 'ALIGN_TOPCENTER',
            MediaConst::ALIGN_TOPRIGHT => 'ALIGN_TOPRIGHT',
            MediaConst::ALIGN_BOTTOMLEFT => 'ALIGN_BOTTOMLEFT',
            MediaConst::ALIGN_BOTTOMCENTER => 'ALIGN_BOTTOMCENTER',
            MediaConst::ALIGN_BOTTOMRIGHT => 'ALIGN_BOTTOMRIGHT',
            MediaConst::ALIGN_CENTER => 'ALIGN_CENTER'
            ];
        return isset($table[$align]) ? $table[$align] : '(unknown align)';
    }
    
    /**
     * Returns a string description for an sizing constant.
     * @param int $sizing A 'SIZE_*' constant
     * @return string
     */
    protected function _getSizingDescription($sizing) {
        $table = [
            MediaConst::SIZE_ZOOM => 'SIZE_ZOOM',
            MediaConst::SIZE_CLIP => 'SIZE_CLIP',
            MediaConst::SIZE_STRETCH => 'SIZE_STRETCH',
            MediaConst::SIZE_SHRINK => 'SIZE_SHRINK'
            ];
        return isset($table[$sizing]) ? $table[$sizing] : '(unknown sizing)';
    }
    
    /**
     * Returns a string description for an image type.
     * @param int $type An 'IMAGETYPE_*' constant
     * @return string
     */
    protected function _getImageTypeDescription($type) {
        $table = [
            IMAGETYPE_PNG       => 'PNG image',
            IMAGETYPE_JPEG      => 'JPEG image',
            IMAGETYPE_GIF       => 'GIF image',
            IMAGETYPE_JPEG2000  => 'JPEG2000 image',
            IMAGETYPE_SWF       => 'SWF image',
            IMAGETYPE_PSD       => 'PSD image',
            IMAGETYPE_BMP       => 'BMP image',
            IMAGETYPE_WBMP      => 'WBMP image',
            IMAGETYPE_XBM       => 'XBM image',
            IMAGETYPE_TIFF_II   => 'TIFF_II image',
            IMAGETYPE_TIFF_MM   => 'TIFF_MM image',
            IMAGETYPE_IFF       => 'IFF image',
            IMAGETYPE_JB2       => 'JB2 image',
            IMAGETYPE_JPC       => 'JPC image',
            IMAGETYPE_JP2       => 'JP2 image',
            IMAGETYPE_JPX       => 'JPX image',
            IMAGETYPE_SWC       => 'SWC image',
            IMAGETYPE_ICO       => 'ICO image',
            IMAGETYPE_WEBP      => 'WEBP image',
            MediaConst::IMAGETYPE_UNKNOWN => '(undefined/default)',
            ];
        return isset($table[$type]) ? $table[$type] : '(unknown PHP image type)';
    }
    
    /**
     * 
     * @param int $options
     * @return string
     */
    protected function _getOptionsDescription($options) {
        $table = [
            MediaConst::IMG_OPTION_OVERWRITE => 'IMG_OPTION_OVERWRITE',
            MediaConst::IMG_OPTION_RENAME => 'IMG_OPTION_RENAME',
            MediaConst::IMG_OPTION_ADD_FILE_EXTENSION => 'IMG_OPTION_ADD_FILE_EXTENSION',
            MediaConst::IMG_OPTION_OVERLAY_BEFORE_FILTER => 'IMG_OPTION_OVERLAY_BEFORE_FILTER',
            MediaConst::IMG_OPTION_OVERLAY_AFTER_FILTER => 'IMG_OPTION_OVERLAY_AFTER_FILTER',
            MediaConst::IMG_OPTION_OVERLAY_SIZE_TO_FIT => 'IMG_OPTION_OVERLAY_SIZE_TO_FIT',
            MediaConst::IMG_OPTION_OVERLAY_REPEAT => 'IMG_OPTION_OVERLAY_REPEAT',
            ];
        $intOptions = (int)$options;
        $return = [];
        foreach($table as $key => $text) {
            if($intOptions & $key) {
                $return[] = $text;
            }
        }
        return implode(' ', $return);
    }
}
