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
    Description : IPTC (International Press Telecommunications Council) image functions
                  Reads/writes IPTC information to/from JPEG images.
   
    @see php.net/manual/en/function.iptcembed.php
    @see iptc.org/standards/photo-metadata/photo-metadata/  
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\PhpErrorHandler;

/**
 * Created on  : Jan 01, 2016, 12:00:00 PM
 * Organization: Pro Computer
 * Author      : James R. Steel
 * Description : PHP Software by Pro Computer.
 *               IPTC (International Press Telecommunications Council) image functions
 *               Reads/writes IPTC information to/from JPEG images.
 * 
 * @see php.net/manual/en/function.iptcembed.php
 * @see iptc.org/standards/photo-metadata/photo-metadata/  
*/
class Iptc {
    /**
     * Adds IPTC metadata information to a image file.
     *
     * @param string $sourceFile JPEG image file in which to embed field.
     * @param string $destFile   Output file path. Can be same as $sourceFile
     * @param array  $fields     IPTC field number=>content pairs
     * 
     * @return boolean Returns TRUE if success else FALSE.
     * 
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     * 
     * @see php.net/manual/en/function.iptcembed.php
     */
    public function addIPTCField($sourceFile, $destFile, array $fields) {
        /*
          --- The most common IPTC Fields
          005 - Object Name
          007 - Edit Status
          010 - Priority
          015 - Category
          020 - Supplemental Category
          022 - Fixture Identifier
          025 - Keywords
          030 - Release Date
          035 - Release Time
          040 - Special Instructions
          045 - Reference Service
          047 - Reference Date
          050 - Reference Number
          055 - Created Date
          060 - Created Time
          065 - Originating Program
          070 - Program Version
          075 - Object Cycle
          080 - Byline
          085 - Byline Title
          090 - City
          095 - Province State
          100 - Country Code
          101 - Country
          103 - Original Transmission Reference
          105 - Headline
          110 - Credit
          115 - Source
          116 - Copyright String
          120 - Caption
          121 - Local Caption */

        if(! function_exists('iptcembed')) {
            // cannot parse nor embed image IPTC code: IPTC functions are not enabled
            throw new Exception\InvalidArgumentException(MediaConst::T_IPTC_NO_FUNCTION, MediaConst::E_IPTC_NO_FUNCTION);
        }

        // Throw an error if the image file invalid.
        $dummy = $this->_getImageProperties($sourceFile, true);

        // Convert IPTC tags into binary code
        $data = [];
        foreach($fields as $field => $value) {
            $num = is_numeric($field) ? intval($field) : -1;
            if($num < 1 || $num > 255) {
                $msg = sprintf(MediaConst::T_IPTC_INVALID_IPTC_FIELD, Types::getVartype($field));
                throw new Exception\RuntimeException($msg, MediaConst::E_IPTC_INVALID_IPTC_FIELD);
            }
            $tag = str_pad(strval($num), 3, '0', STR_PAD_LEFT);
            $data[] = $this->_iptcMakeTag(2, $tag, $value);
        }

        // Embed the IPTC data
        $phpErrorHandler = new PhpErrorHandler();
        $strData = implode('', $data);
        $content = $phpErrorHandler->call(function()use($strData, $sourceFile){
            return iptcembed($strData, $sourceFile);
        });
        if(false === $content) {
            $msg = $phpErrorHandler->getErrorMsg("iptcembed() function failed", "cannot embed IPTC code");
            throw new Exception\RuntimeException($msg, MediaConst::E_IPTC_CANNOT_EMBED);
        }

        // Write the new image data out to the file.
        $handle = $phpErrorHandler->call(function()use($destFile){ return fopen($destFile, "wb"); });
        if(false === $handle) {
            $msg = $phpErrorHandler->getErrorMsg("fopen() function failed", "cannot open file for writing");
            throw new Exception\RuntimeException($msg, MediaConst::E_IPTC_CANNOT_OPEN);
        }
        
        $res = $phpErrorHandler->call(function()use($handle, $content){ return fwrite($handle, $content); });
        if(! $res) {
            $msg = $phpErrorHandler->getErrorMsg("fwrite() function failed", "cannot write IPTC code to image file");
            $code = MediaConst::E_IPTC_CANNOT_WRITE;
        }
        
        // Close handle, ignore errors.
        $phpErrorHandler->call(function()use($handle){ return fclose($handle); });
        
        if(! $res) {
            throw new Exception\RuntimeException($msg, $code);
        }
        return true;
    }

    /**
     * Get IPTC information from a file.
     * 
     * @param string  $file         Image file from which to extract IPTC information.
     * @param mixed   $IPTCFields   (optional) An IPTC field number or array of field numbers.
     * @param string  $section      (optional) Section from which to return IPTC information.
     * 
     * @return array|boolean  Returns IPTC information array or FALSE if the function failed.
     * 
     * @throws Exception\InvalidArgumentException
     * 
     * @see php.net/manual/en/function.iptcparse.php
     */
    public function getIPTCField($file, $IPTCFields = null, $section = 'APP13') {
        if(! function_exists('iptcparse')) {
            // cannot parse nor embed image IPTC code: IPTC functions are not enabled
            throw new Exception\InvalidArgumentException(MediaConst::T_IPTC_NO_FUNCTION . ': iptcparse()', MediaConst::E_IPTC_NO_FUNCTION);
        }

        // Throw an error if the image file invalid.
        $properties = $this->_getImageProperties($file, true);
        
        $info = $properties['info'];
        
        if(! isset($info[$section])) {
            return false;
        }
        
        $section = $info[$section];
        $phpErrorHandler = new PhpErrorHandler();
        $iptc = $phpErrorHandler->call(function()use($section){ return iptcparse($section); });
        if(! is_array($iptc)) {
            return false;
        }
        if(empty($iptc)) {
            return [];
        }
        $items = [];
        foreach($iptc as $key => $val) {
            if(preg_match('/[0-9]+\#([0-9]+)/', $key, $matches)) {
                $key = intval($matches[1]);
            }
            $items[$key] = $val;
        }
        if(empty($IPTCFields)) {
            return $items;
        }
        $return = [];
        foreach((array)$IPTCFields as $field) {
            if(isset($items[intval($field)])) {
                $return[$field] = $items[intval($field)];
            }
            /*
            array (size=2)
              '2#116' =>
                array (size=1)
                  0 => string 'Copyright© 2017 Pro Computer Consultants®' (length=43)
              '2#120' =>
                array (size=1)
                  0 => string 'Pro Computer Consultants® hang glider' (length=38)
             */
        }
        return $return;
    }

    /**
     * Returns image properties
     *
     * @param string $file Image file for which to get information.
     * @return array
     * @throws Exception\InvalidArgumentException
     */
    protected function _getImageProperties($file, $throw = true) {
        $imgPropertiesObj = new ImageProperties();
        try {
            $properties = $imgPropertiesObj($file);
            if(! $properties['errno']) {
                return $properties;
            }
            $code = $properties['errno'];
            $errorMsg = $properties['error'];
        } catch(\Throwable $ex) {
            $code = $ex->getCode();
            $errorMsg = $ex->getMessage();
            $properties = [];
        }
        if($throw) {
            throw new Exception\InvalidArgumentException($errorMsg, $code);
        }
        return $properties;
    }
    
    /**
     * Create a photo information tag.
     * @param int    $rec
     * @param int    $data
     * @param string $value
     * @return string
     */
    protected function _iptcMakeTag($rec, $data, $value) {
        $value = strval($value);
        $length = strlen($value);
        $retval = chr(0x1C) . chr($rec) . chr($data);
        if($length < 0x8000) {
            $retval .= chr($length >> 8) .  chr($length & 0xFF);
        }
        else {
            $retval .= chr(0x80) .
                       chr(0x04) .
                       chr(($length >> 24) & 0xFF) .
                       chr(($length >> 16) & 0xFF) .
                       chr(($length >> 8) & 0xFF) .
                       chr($length & 0xFF);
        }
        return $retval . $value;
    }

}