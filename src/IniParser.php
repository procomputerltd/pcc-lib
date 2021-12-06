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
    Description : Parses a PHP .ini file.
*/
namespace Procomputer\Pcclib;

/**
 * Parses a PHP .ini file.
 */
class IniParser {

    const PARAM_VALUES = '__ini_values__';
    const PARAM_COMMENTS = '__ini_comments__';
    const PARAM_DUPLICATES = '__duplicates__';

    protected $_iniValues = array(
        self::PARAM_VALUES => [],
        self::PARAM_COMMENTS => [],
        self::PARAM_DUPLICATES => []
        );

    /**
     * Constructor
     * @param string $file (optional) File to parse.
     */
    public function __construct($file = null) {
        if(null !== $file) {
            $this->_parseIni($file);
        }
    }

    /**
     * Returns parsed ini values.
     * @param string $file (optional) File to parse.
     */
    public function getValues($file = null) {
        if(null !== $file) {
            $this->_parseIni($file);
        }
        return $this->_iniValues[self::PARAM_VALUES];
    }

    /**
     * Returns parsed ini comments.
     * @param string $file (optional) File to parse.
     */
    public function getDuplicates($file = null) {
        if(null !== $file) {
            $this->_parseIni($file);
        }
        return $this->_iniValues[self::PARAM_DUPLICATES];
    }

    /**
     * Returns parsed ini duplicate value names.
     * @param string $file (optional) File to parse.
     */
    public function getComments($file = null) {
        if(null !== $file) {
            $this->_parseIni($file);
        }
        return $this->_iniValues[self::PARAM_COMMENTS];
    }

    public function getTable($returnDuplicates = false) {
        $iniValues = array(self::PARAM_VALUES => [], self::PARAM_DUPLICATES => []);
        foreach($this->_iniValues[self::PARAM_VALUES] as $propName => $items) {
            foreach($items as $propValue) {
                $name = $propName;
                if(isset($iniValues[self::PARAM_VALUES][$name])) {
                    $name .= "({$propValue})";
                }
                if($returnDuplicates && isset($iniValues[self::PARAM_VALUES][$name])) {
                    $iniValues[self::PARAM_DUPLICATES] = $name;
                }
                else {
                    $iniValues[self::PARAM_VALUES][$name] = $propValue;
                }
            }
        }
        return $returnDuplicates ? $iniValues : $iniValues[self::PARAM_VALUES];
    }

    /**
     *
     * @param string $file File to parse.
     */
    protected function _parseIni($file) {
        $data = array_map('trim', explode("\n", preg_replace('/[\r\n]+/', "\n", file_get_contents($file))));
        $this->_iniValues = array(self::PARAM_VALUES => [], self::PARAM_COMMENTS => []);
        foreach($data as $line) {
            if(strlen($line)) {
                if(preg_match('/^[ \t]*\#(.*)$/', $line, $matches)) {
                    // # a comment
                    $this->_iniValues[self::PARAM_COMMENTS][] = $matches[1];
                }
                elseif(preg_match('/^(.+?)=(.*)$/', $line, $matches)) {
                    // xdebug.remote_enable = 1
                    $matches = array_map('trim', $matches);
                    if(strlen($matches[1])) {
                        $directive = $matches[1];
                        $val = $matches[2];
                        $valLen = strlen($val);
                        if($valLen) {
                            $q = $val[0];
                            if('"' === $q || "'" === $q) {
                                $val = $this->_unquote($val);
                            }
                            else {
                                $rem = strpos($val, '#');
                                if(false !== $rem) {
                                    $val = trim(substr($val, 0, $rem));
                                }
                            }
                        }
                        // Group identical directives like 'extension'
                        $this->_iniValues[self::PARAM_VALUES][$directive][] = $val;
                        if(count($this->_iniValues[self::PARAM_VALUES][$directive]) > 1) {
                            $this->_iniValues[self::PARAM_DUPLICATES][$directive] = $directive;
                        }
                    }
                }
            }
        }
    }

    protected function _unquote($val, $esc = "\\") {
        $len = strlen($val);
        if(! $len) {
            return '';
        }
        $q = $val[0];
        if('"' !== $q && "'" !== $q) {
            return $val;
        }
        $str = [];
        for($i = 1; $i < $len; $i++) {
            $c = substr($val, $i, 1);
            if($esc === $c) {
                if(++$i >= $len) {
                    break;
                }
                $c = substr($val, $i, 1);
            }
            elseif($c === $q) {
                break;
            }
            $str[] = $c;
        }
        return implode('', $str);
    }
}
