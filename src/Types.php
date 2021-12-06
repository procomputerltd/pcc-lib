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
    Description : Helpers to handle data type validation and to create human-readable display of variables using 'getVarType()'
*/
namespace Procomputer\Pcclib;

/**
 * Helpers to handle data type validation and to create human-readable display of variables using 'getVarType()'
 */
class Types {

    /**
     * Validates an IDENTITY value that must be a positive integer (or zero if $allowZero=true) or all-digit string.
     * @param string|int $value     Value to validate.
     * @param boolean    $allowZero (optional) Allow zero value.
     * @param boolean    $toInt     (optional) Returns value converted to integer.
     * @return mixed
     * @throws \Exception
     */
    public static function validNumericIdentity($value, $allowZero = false, $toInt = false) {
        if(is_int($value)) {
            if($value >= ($allowZero ? 0 : 1)) {
                return $value;
            }
        }
        elseif(is_string($value) && ctype_digit($value)) {
            if('0' !== $value || $allowZero) {
                if(! $toInt) {
                    return $value;
                }
                $int = (int)$value;
                if(strval($int) === $value) {
                    return $int;
                }
            }
        }
        $var = self::getVartype($value);
        $allow = $allowZero ? ' zero or' : '';
        $range = $toInt ? (" <= " . PHP_INT_MAX) : '';
        $msg = "Invalid IDENTITY value '{$var}': expecting{$allow} positive integer{$range}";
        throw new Exception\InvalidArgumentException($msg);
    }

    /**
     * Returns a terse string representation of a variable.
     *
     * @param mixed $mixed     Variable for which to get variable type.
     * @param int   $maxstrlen (optional) Maximum length of returned string representation of the variable.
     *
     * @return string   Return string representation of the variable.
     */
    public static function getVartype($mixed, $maxstrlen = 255) {
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

    /**
     * Validates a floating point number or string.
     *
     * @param mixed   $number   Value to check for being float.
     *
     * @return boolean  Returns TRUE when '$number' may be interpreted as a floating point number.
     */
    public static function isFloat($number) {
        if(is_int($number)) {
            // It's a PHP integer.
            return true;
        }
        if(is_float($number)) {
            // NAN if conversion to string includes pound.
            if(is_nan($number)) {
                return false;
            }
            // NAN if conversion to string includes pound.
            $strNum = strval($number);
            if(is_numeric($number)) {
                return true;
            }
            return false !== strpos($strNum, '#');
        }
        if(!is_string($number)) {
            // NAN - non-string.
            return false;
        }
        $number = trim($number);
        if(ctype_digit($number) && strlen($number) < 308) {
            // It's a string of digits.
            return true;
        }
        if(false !== strpos(strtolower($number), "e") && preg_match('/[0-9\.]+e[\s]*[0-9\+\-]+$/i', $number)) {
            $f = floatval($number);
            if(!$f) {
                // NAN
                return false;
            }
            return (false === strpos(strval($f), '#')) ? true : false;
        }
        $parser = new NumberParser($number);
        // Return result from the number parser class.
        return $parser->isValidNumber();
    }

    /**
     * Returns TRUE when the parameter is an integer number or string.
     *
     * @param mixed   $number   Value to check for being integer.
     *
     * @return boolean  Returns TRUE when the parameter is an integer number else FALSE.
     */
    public static function isInteger($number) {
        if(is_int($number)) {
            // It's a PHP integer.
            return true;
        }
        if(is_float($number)) {
            // Max integer values are platform-dependent but use PHP_INT_MAX in comparisons.
            // 32-bit signed int max,min = 2147483647, -2147483648
            return ($number <= PHP_INT_MAX && $number >= (-PHP_INT_MAX - 1) && intval($number) == $number) ? true : false;
        }
        if(!is_string($number)) {
            // NAN - not string.
            return false;
        }
        $number = trim($number);
        if(!is_numeric($number)) {
            // NAN - blank or non-numeric string.
            return false;
        }
        if(ctype_digit($number) && strlen($number) < 10) {
            // It's a string of digits.
            return true;
        }
        $parser = new NumberParser($number);
        // Max integer values are platform-dependent but use PHP_INT_MAX in comparisons.
        // 32-bit signed int max,min = 2147483647, -2147483648
        return $parser->isInteger(-PHP_INT_MAX - 1, PHP_INT_MAX);
    }

    /**
     * Returns TRUE when the parameter is a boolean.
     *
     * @param mixed $mixed       Value to check for being boolean.
     *
     * @return boolean Return TRUE when the parameter is boolean.
     */
    public static function isBool($mixed) {
        return (bool)(is_bool($mixed) || self::isFloat($mixed));
    }

    /**
     * Returns the boolean value, TRUE or FALSE, for the parameter.
     *
     * @param mixed $mixed       Value for which to get boolean value.
     *
     * @return boolean   Return boolean value of the parameter.
     */
    public static function boolVal($mixed) {
        if(is_bool($mixed)) {
            return $mixed;
        }
        if(self::isFloat($mixed)) {
            return floatval($mixed) ? true : false;
        }
        return false;
    }

    /**
     * Returns TRUE when the parameter is unset, null or an empty string or is all whitespace
     * ($allow_white=false). Returns FALSE when the parameter variable is not a string including
     * an empty array.
     *
     * @param mixed   $var          Value to check for being blank.
     * @param boolean $allowWhite  (optional) When TRUE, this method considers an all whitespace string NOT-BLANK.
     *
     * @return boolean   Returns TRUE when the parameter is unset, null or an empty string or is
     *                   all whitespace ($allowWhite=false)
     */
    public static function isBlank($var, $allowWhite = false) {
        // Unset or null are blank.
        if(!isset($var) || is_null($var)) {
            return true;
        }
        // Non-string scalars, resources, objects are not blank.
        if(!is_string($var)) {
            return false;
        }
        // Empty string is blank.
        if(!strlen($var)) {
            return true;
        }
        if($allowWhite) {
            // Non-empty string is NOT blank.
            return false;
        }
        // All whitespace([ \n\r\t\v\f]) is blank.
        return ctype_space($var);
    }
}
