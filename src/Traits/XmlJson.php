<?php
namespace Procomputer\Pcclib\Traits;

/* 
 * Copyright (C) 2022 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
use Procomputer\Pcclib\Types;
use stdClass, RuntimeException;

trait XmlJson {
    
    protected $lastXmlJsonError = '';
    
    /**
     * 
     * @param string $xmlString
     * @return object|boolean
     */
    public function xmlLoadString($xmlString) {
        /*
         * Check the XML using DOMDocument. If valid load the XML using simplexml_load_string.
         */
        // $version = '1.0', $encoding = 'utf-8'
        $doc = new \DOMDocument('1.0', 'utf-8');
        $res = new stdClass();
        $res->fail = false;
        $res->error = '';
        $errorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use($res) {
            $res->fail = true;
            $res->error = $errstr;
        });
        try {
            if($doc->loadXML($xmlString)) {
                // $manifest = simplexml_load_string($xmlString);
                $manifest = $this->xml_to_array($doc->documentElement);
                if(false !== $manifest) {
                    $manifest = $this->arrayToObject($manifest);
                    if($manifest) {
                        return $manifest;
                    }
                }
            }
            else {
                $msg = $res->error;
            }
        } catch (\Throwable $ex) {
            $msg = "{$ex->getMessage()} {$ex->getFile()} ({$ex->getLine()})";
        } finally {
            set_error_handler($errorHandler);
        }
        if(empty($msg)) {
            $msg = "The manifest XML data cannot be interpreted as XML";
        }
        $this->lastXmlJsonError = $msg;
        return false;
    }

    /**
     * 
     * @param \DOMDocument $root
     * @return type
     */
    public function xml_to_array($root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->xml_to_array($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->xml_to_array($child);
                }
            }
        }

        return $result;
    }    
    
    /**
     * Encodes a value into a JSON string.
     * @param mixed   $value   Value to convert to JSON string.
     * @param int     $flags   (optional) When true, JSON objects will be returned as associative arrays else objects
     * @param int     $depth   (optional) Max depth allowed to be encoded.
     * @return string|boolean  A json string or false on error.
     */
    public function jsonEncode($value, $flags = 0, $depth = 512) {
        $json = json_encode($value, $flags, $depth);
        if(false !== $json && null !== $json) {
            return $json;
        }
        $jmsg = json_last_error_msg();
        $f = __FUNCTION__;
        $var = Types::getVartype($value);
        $msg = "in $f() the 'value' parameter '{$var}' cannot be converted to JSON";
        if(! Types::isBlank($jmsg)) {
            $msg . ': ' . $jmsg;
        }
        $this->lastXmlJsonError = $msg;
        return false;
    }
    
    /**
     * Decodes a JSON string into an object or, optionally an array.
     * @param string  $json    JSON string.
     * @param boolean $assoc   (optional) When true, JSON objects will be returned as associative arrays else objects
     * @param int     $options (optional)  One or more 'JSON_*' constants
     * @return stdClass|array|boolean  An array or object decoded from the json string or false on error.
     */
    public function jsonDecode($json, $assoc = false, $options = 0) {
        $obj = json_decode($json, $assoc, 512, $options);
        if(false !== $obj && null !== $obj) {
            return $obj;
        }
        $jmsg = json_last_error_msg();
        $f = __FUNCTION__;
        $var = Types::getVartype($json);
        $msg = "in $f() the 'json' parameter '{$var}' cannot be interpreted as JSON";
        if(! Types::isBlank($jmsg)) {
            $msg . ': ' . $jmsg;
        }
        $this->lastXmlJsonError = $msg;
        return false;
    }
    
    /**
     * Converts an XML string to an object.
     * @param string $xml
     * @return stdClass
     * @throws RuntimeException
     */
    public function xmlToObject($xml) {
        $json = $this->jsonEncode($xml /*, JSON_PRETTY_PRINT */);
        if(false !== $json) {
            $array = $this->jsonDecode($json, true);
            if(false !== $array) {
                $obj = $this->arrayToObject($array);
                return $obj;
            }
        }
        return false;
    }
    
    /**
     * Converts recursively an array to stdClass objects.
     * @param array|\Traversable $traversable An array or iterable\traversable collection.
     * @return stdClass
     */
    public function arrayToObject($traversable) {
        if(! is_array($traversable) && ! $traversable instanceof \Traversable) {
            $f = __FUNCTION__;
            $var = Types::getVartype($traversable);
            $msg = "in $f() the 'traversable' parameter is '{$var}': expecting array of Traversable";
            $this->lastXmlJsonError = $msg;
            return false;
        }
        $obj = new stdClass;
        foreach($traversable as $k => $v) {
            if(strlen($k)) {
                if(is_array($v) || $v instanceof \Traversable) {
                    $obj->{$k} = $this->arrayToObject($v); //RECURSION
                } else {
                    $obj->{$k} = $v;
                }
            }
        }
        return $obj;
    }   
}