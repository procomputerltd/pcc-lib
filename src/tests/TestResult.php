<?php
/* 
 * Copyright (C) 2018 Pro Computer James R. Steel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
namespace Procomputer\Pcclib\Tests;

use Procomputer\Pcclib;

class TestResult {
    
    public $classpath = '';
    public $method = '';
    public $params = [];
    public $success = false;
    public $results = [];
    public $resultType = null;
    public $error = null;
    public $throwsExceptions = false;
    public $threwException = false;

    /**
     * The maximum parameter length allowed.
     * 
     * @var int
     */
    protected $_maxParamLen = -1;

    /**
     * Constructor
     * 
     * @param string   $classpath
     * @param string   $method
     * @param array    $params
     * @param boolean  $success
     * @param mixed    $results
     * @param string   $resultType
     * @param Error    $error
     * @param boolean  $throwsExceptions
     * @param boolean  $threw
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct($classpath, $method, $params, $success, $results, $resultType = null, Pcclib\Error $error = null, $throwsExceptions = false, $threw = false) {
        $this->classpath = (string)$classpath;
        if(! is_string($method)) {
            $badParam = 'method';
        }
        else {
            $this->method = $method;
            if(! is_array($params)) {
                if($params instanceof \Traversable) {
                    $params = (array)$params;
                }
                else {
                    if(null === $params) {
                        $params = 'NULL';
                    }
                    elseif(! is_scalar($params)) {
                        $params = gettype($params);
                    }
                    $params = [$params];
                }
            }
            $this->params = $params;
            
            if(! is_bool($success)) {
                $badParam = 'success';
            }
            else {
                $this->success = $success;
            }
        }
        $this->results = is_array($results) ? $results : [$results];
        $this->resultType = $resultType;
        $this->error = $error;
        $this->throwsExceptions = $throwsExceptions;
        $this->threwException = $threw;
        
        if(isset($badParam)) {
            $var = $this->_getVartype($$badParam);
            throw new \InvalidArgumentException("Invalid '{$badParam}' parameter: {$var}");
        }
    }

    /**
     * Returns the maximum parameter length allowed.
     * @return int
     */
    public function getMaxParamLen() {
        return $this->_maxParamLen;
    }
    
    /**
     * Sets the maximum parameter length allowed.
     * @param int $len
     * @return $this
     */
    public function setMaxParamLen($len) {
        $this->_maxParamLen = (int)$len;
        return $this;
    }
    
    /**
     * Returns a unique key for the test method and its parameters.
     * @return string
     */
    public function getMethodParamKey() {
        $params = [];
        foreach($this->params as $p) {
            $params[] = $this->_getVartype($p);
        }
        // Add the test results indexed by classpath/method+params
        return md5($this->method . '_' . implode('_', $params));
    }
    
    public function getParamsAsHtml() {
        /* @var $testResult TestResult */
        $items = [];
        $pattern = '~^' . chr(1) . '(.+?)' . chr(1) . '(.+)$~s';
        foreach($this->params as $param) {
            if(is_scalar($param)) {
                if(is_string($param)) {
                    if(preg_match($pattern, $param, $m)) {
                        $param = $m[2];
                        if('text/html' === $m[1]) {
                            // HTML string.
                        }
                    }
                    else {
                        $max = $this->getMaxParamLen();
                        if($max > 4 && strlen($param) > $max) {
                            $param = substr($param, 0, $max - 4) . '...';
                        }
                        $param = '"' . htmlentities($param) . '"';
                    }
                }
                else {
                    $param = htmlentities($this->_getVartype($param));
                }
            }        
            elseif(is_array($param)) {
                $param = '<pre>    ' . htmlentities(str_replace("\n", "\n    ", print_r($param, true))) . '</pre>';
            }
            else {
                $param = htmlentities($this->_getVartype($param));
            }
            $items[] = $param;
        }
        return implode(', ', $items);
    }
    
    /**
     * Returns a terse string representation of a variable.
     *
     * @param mixed $mixed     Variable for which to get variable type.
     * @param int   $maxstrlen (optional) Maximum length of returned string representation of the variable.
     *
     * @return string   Return string representation of the variable.
     */
    protected function _getVartype($mixed, $maxstrlen = 255) {
        if(!isset($mixed) || null === $mixed) {
            return "(null)";
        }
        if(is_string($mixed)) {
            if(!($l = strlen($mixed))) {
                return "(empty string)";
            }
            if($l > $maxstrlen && $maxstrlen > 0) {
                if($maxstrlen < 10) {
                    $maxstrlen = 10;
                }
                if($l <= $maxstrlen) {
                    return $mixed;
                }
                $half = intval($maxstrlen / 2);
                return substr($mixed, 0, $maxstrlen - $half) . "..." . substr($mixed, $l - $half);
            }
            return $mixed;
        }
        if(is_array($mixed)) {
            return "array(" . count($mixed) . ")";
        }
        if(is_bool($mixed)) {
            return $mixed ? "(true)" : "(false)";
        }
        if(is_int($mixed)) {
            return '(int)' . $mixed;
        }
        if(is_float($mixed)) {
            return '(float)' . $mixed;
        }
        if(is_object($mixed)) {
            return "(object " . get_class($mixed) . ")";
        }
        if(is_resource($mixed)) {
            return "(resource " . get_resource_type($mixed) . ")";
        }
        /* gettype() possible value strings returned are:
          "string"
          "array"
          "boolean"
          "integer"
          "double" ("double" is returned in case of a float, and not simply "float")
          "object"
          "resource"
          "NULL"
          "unknown type" */
        return gettype($mixed); // unknown type
    }

}
