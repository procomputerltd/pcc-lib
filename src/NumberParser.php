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
    Description : Parses numerics including numeric strings and rounds and formats decimal number strings from the parsed number.
*/
namespace Procomputer\Pcclib;

use Procomputer\Pcclib\Error;

/**
 * Parses and validates numerics including numeric strings, hexadecimals, exponential representations
 * Then use 'formatNumber()' to format number strings from the parsed number.
 *
 * Examples:
 *
 *    // Try passing an invalid number PHP reports as 'INF', infinite number, unrepresentable.
 *    // Floating point maximum is platform-dependant. Largest in Windblows <float.h> header
 *    // is 1.7976931348623158e+308, smallest 2.2250738585072014e-308
 *    $parser = new NumberParser();
 *    $number = '1.99e+309';
 *    if(! $parser($number)) {
 *      throw new \Exception("{$number} is not a valid number!", -1);
 *    }
 *    else {
 *      $stringNumberRounded = $parser->formatNumber(4, '.', ',');
 *    }
 *    // Pass a valid number.
 *    $number = '999999999999999999999999999.99999999';
 *    if(! $parser($number)) {
 *      throw new \Exception("{$number} is not a valid number!", -1);
 *    }
 *    else {
 *      $stringNumberRounded = $parser->formatNumber(8, '.', ',');
 *    }
 */
class NumberParser extends Common {

    const MAX_DECIMALS = 32767;

    /**
     * Set TRUE when the parsed value is a number, else FALSE if invalid.
     * @var boolean
     */
    protected $_valid = false;

    /**
     * Holds the whole number part of the parsed number.
     * @var string|float
     */
    protected $_number = "";

    /**
     * Holds the fractional part of the parsed number.
     * @var string|float
     */
    protected $_fraction = "";

    /**
     * Set TRUE when the parsed number is negative, else FALSE.
     * @var boolean
     */
    protected $_sign = false;

    /**
     * Set TRUE when the parsed number is an integer, that is, has no fractional part.
     * @var boolean
     */
    protected $_integer = false;

    /**
     * Switch: throw errors or return an Error object when a major error occurrs.
     * @var boolean
     */
    private static $_throwErrors = true;

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
     * Constructor
     *
     * @param mixed  $number The number being parsed.
     */
    public function __construct($number = null) {
        if(null !== $number) {
            $error = $this->parseNumber($number);
            if($error instanceof Error) {
                throw new Exception\RuntimeException($error->getMessage(), $error->getCode());
            }
        }
    }

    /**
     * __invoke is called when the class is used like a function.
     *
     * Example: $parser = new NumberParser();
     *          $parser($number);
     *          $isValid = $parser->isValidNumber()
     *          if(! $isValid) {
     *
     * @param mixed  $number The number being parsed.
     */
    public function __invoke($number = null) {
        if(null !== $number) {
            return $this->parseNumber($number);
        }
        return $this;
    }

    /**
     * Returns a class property.
     * @param string $name
     * @return mixed Returns the property value.
     * @throws Exception\RuntimeException
     */
    public function __get($name) {
        $var = '_' . $name;
        if(isset($this->$var)) {
            return $this->$var;
        }
        $var = Types::getVartype($name);
        throw new Exception\RuntimeException("property '{$var}' not found");
    }

    /**
     * Parses a number and stores the parts in this object's properties.
     *
     * @param string $number  Number to parse.
     *
     * @return boolean  Returns TRUE if successful or FALSE if parse fails.
     */
    public function parseNumber($number) {
        $this->_number = $this->_fraction = "";
        $this->_valid = $this->_sign = $this->_integer = false;

        /**
         * Parameter validation - accept only integer, float, string
         */
        if(! is_scalar($number) || is_bool($number) || is_string($number) && ! strlen(trim($number))) {
            // T_PARAMETER_INVALID = "invalid '%s' parameter '%s'"; // @var string
            $msg = sprintf(Constant::T_PARAMETER_INVALID, 'number', Types::getVartype($number))
                . ": expecting a number or numeric string";
            if(self::$_throwErrors) {
                throw new Exception\InvalidArgumentException($msg, Constant::E_PARAMETER_INVALID);
            }
            return new Error($msg, Constant::E_PARAMETER_INVALID);
        }

        $num = $number; // Preserve the original number variable.
        if(is_int($num)) {
            // It's a PHP integer or int|float zero.
            $this->_valid = $this->_integer = true;
            if($num) {
                $this->_number = abs($num);
                $this->_sign = (bool)($num < 0);
            }
            return true;
        }

        if(is_string($num) && preg_match('/^[0-9\\.]+[eE][\\+\\-]?[0-9\\.]+$/', $num)) {
            $num = floatval($num);
        }
        if(is_float($num)) {
            if(0.0 === $num) {
                $this->_valid = true;
                return true;
            }
            // It's a PHP float/double.
            if(is_infinite($num) || ($nan = is_nan($num))) {
                // the parameter that specifies the number is not-a-number (NAN).
                // the parameter that specifies the number is not a number, infinite (INF).
                $this->_lastErrorCode = isset($nan) ? Constant::E_PARSENUMBER_NAN : Constant::E_PARSENUMBER_INF;
                return false;
            }
            $whole = floor($num);
            if(is_infinite($whole) || ($nan = is_nan($whole))) {
                // the parameter that specifies the number is not-a-number (NAN).
                // the parameter that specifies the number is not a number, infinite (INF).
                $this->_lastErrorCode = isset($nan) ? Constant::E_PARSENUMBER_NAN : Constant::E_PARSENUMBER_INF;
                return false;
            }
            $fraction = $num - $whole;
            if(is_infinite($fraction) || ($nan = is_nan($fraction))) {
                // the parameter that specifies the number is not-a-number (NAN).
                // the parameter that specifies the number is not a number, infinite (INF).
                $this->_lastErrorCode = isset($nan) ? Constant::E_PARSENUMBER_NAN : Constant::E_PARSENUMBER_INF;
                return false;
            }
            $this->_number = $whole ? $whole : '';
            $this->_fraction = $fraction ? $fraction : '';
            $this->_sign = (bool)($num < 0);
            $this->_valid = true;
            return true;
        }

        $num = trim($num);
        $len = strlen($num);
        if(! $len) {
            // the parameter that specifies the number is empty.
            $this->_lastErrorCode = Constant::E_PARSENUMBER_EMPTY;
            return false;
        }

        // All zeroes?
        if($len === strspn($num, "0")) {
            $this->_valid = $this->_integer = true;
            return true;
        }

        $fraction = "";
        $sign = $integer = false;
        // Check for hexadecimal notation.
        if(preg_match('/^[0]*[xX]([0-9a-fA-F]+)$/', $num, $m)
            || preg_match('/^([0-9a-fA-F]+)$/', $num, $m)) {
            $this->_number = hexdec($m[1]);
            $this->_valid = $this->_integer = true;
            return true;
        }

        // String must now be only digits, decimal point(s) and sign(s)
        // Remove leading sign(s).
        $l = strspn($num, "-+");
        if($l) {
            if(1 != $l || $l == $len) { // Multiple leading pluses or minuses or ALL pluses or minuses.
                // the parameter that specifies the number is not a number, invalid syntax.
                $this->_lastErrorCode = Constant::E_PARSENUMBER_SYNTAX;
                return false;
            }
            $signChar = substr($num, 0, 1);
            $num = trim(substr($num, 1));
            $len = strlen($num);
            if(! $len) {
                // the parameter that specifies the number is not a number, invalid syntax.
                $this->_lastErrorCode = Constant::E_PARSENUMBER_SYNTAX;
                return false;
            }
            if('-' == $signChar) {
                $sign = true; // Negative number.
            }
        }

        // String must now be only digits and decimal point(s)
        if(! preg_match('/^[0-9\\.]+$/', $num)) {
            // the parameter that specifies the number is not a number, invalid syntax.
            $this->_lastErrorCode = Constant::E_PARSENUMBER_SYNTAX;
            return false;
        }

        if(false !== strpos($num, '.')) {
            $arr = explode('.', $num);
            if(count($arr) > 2) {
                // the parameter that specifies the number is not a number, invalid syntax.
                $this->_lastErrorCode = Constant::E_PARSENUMBER_SYNTAX;
                return false;
            }
            $fraction = array_pop($arr);

            $num = array_pop($arr);
            $len = strlen($num);
            if(! $len || $len == strspn($num, '0')) {
                $num = "";
            }

            $len = strlen($fraction);
            if(! $len || $len == strspn($fraction, '0')) {
                $fraction = "";
                $integer = true;
            }
        }
        elseif($len == strspn($num, '0')) {
            $num = $fraction = "";
            $integer = true;
        }

        $this->_number = $num;
        $this->_fraction = $fraction;
        $this->_integer = $integer;
        $this->_sign = $sign;
        $this->_valid = true;
        return true;
    }

    /**
     * Formats the number inserting thousands separator and rounding to the specified decimals.
     *
     * @param int    $decimals     (optional) The number of digits in the number's fractional part.
     * @param string $decimalPoint (optional) The decimal point delimiting the number's fractional part.
     * @param string $thousandSep  (optional) The thousand separator delimiting thousands.
     *
     * @return string Returns formatted number string or FALSE on error.
     */
    public function formatNumber($decimals = null, $decimalPoint = null, $thousandSep = null) {
        if(!$this->_valid) {
            // T_NO_NUMBER_PARSED = "no number was parsed - use method 'parseNumber(\$number)' or specify a number in the constructor.";
            if(self::$_throwErrors) {
                throw new Exception\InvalidArgumentException(Constant::T_NO_NUMBER_PARSED, Constant::E_NO_NUMBER_PARSED);
            }
            return new Error(Constant::T_NO_NUMBER_PARSED, Constant::E_NO_NUMBER_PARSED);
        }

        /**
         * Parameter validation.
         */
        if(is_null($decimals)) {
            $dec = null;
        }
        elseif(!is_numeric($decimals) || ($dec = intval($decimals)) > self::MAX_DECIMALS || $dec < 0) {
            $badParam = 'decimals';
        }
        if(! isset($badParam)) {
            if(is_null($decimalPoint)) {
                $decPoint = null;
            }
            elseif(!is_string($decimalPoint)) {
                $badParam = 'decimalPoint';
            }
            elseif(! strlen($decimalPoint)) {
                $decPoint = null;
            }
            else {
                $decPoint = substr($decimalPoint, 0, 1);
            }
            if(! isset($badParam)) {

                if(is_null($thousandSep)) {
                    $thouSep = null;
                }
                elseif(!is_string($thousandSep)) {
                    $badParam = 'thousandSep';
                }
                elseif(! strlen($thousandSep)) {
                    $thouSep = null;
                }
                else {
                    $thouSep = substr($thousandSep, 0, 1);
                }
            }
        }
        if(isset($badParam)) {
            // T_PARAMETER_INVALID = "invalid '%s' parameter '%s'"; // @var string
            $msg = sprintf(Constant::T_PARAMETER_INVALID. $badParam, Types::getVartype($$badParam));
            if(self::$_throwErrors) {
                throw new Exception\InvalidArgumentException($msg, Constant::E_PARAMETER_INVALID);
            }
            return new Error($msg, Constant::E_PARAMETER_INVALID);
        }

        /**
         * If the number is a float just format
         */
        if(! is_string($this->_number) || ! is_string($this->_fraction)) {
            $num = $this->_number;
            $frac = $this->_fraction;
            if('' === $num && '' === $frac) {
                $num = 0;
            }
            if('' !== $num && '' !== $frac) {
                $num .= '.' . $frac;
            }
            else {
                $num = ('' === $num) ? $frac : $num;
            }
            if(! is_float($num)) {
                $break = 1;
            }
            return number_format($num, $dec, $decPoint, $thouSep);
        }

        $num = $this->_number;
        $frac = $this->_fraction;
        $len = strlen($num);
        $frLen = strlen($frac);

        if(!$len && !$frLen) {
            $num = '0';
        }

        /**
         * Round to the number of decimals specified, if any.
         */
        if(null !== $dec && $dec !== $frLen) {
            if(!$dec) {
                $frac = '';
            }
            else {
                $decDiff = $dec - $frLen;
                if($decDiff > 0) {
                    $frac = str_pad($frac, $dec, '0');
                }
                else {
                    $frac = substr($frac, 0, $dec);
                }
            }
            $frLen = strlen($frac);
        }

        if($len && null !== $thouSep) {
            $num = $this->_addThouSep($num, $thouSep) ;
        }
        if($frLen) {
            $num .= (is_null($decPoint) ? '.' : $decPoint) . $frac;
        }
        if(strlen($num)) {
            if($this->_sign) {
                $num = '-' . $num;
            }
        }
        else {
            $num = '0';
        }
        return $num;
    }

    /**
     * Returns TRUE if the number submitted is valid.
     * @return boolean
     */
    public function isValidNumber() {
        return $this->_valid;
    }

    /**
     * Determines whether the parsed number is an integer.
     *
     * @param float $min    (optional) Minimum integer value allowed.
     * @param float $max    (optional) Maximum integer value allowed.
     *
     * @return boolean Returns TRUE when the parsed number is an integer.
     */
    public function isInteger($min = null, $max = null) {
        if(!$this->_integer) {
            return false;
        }
        if(is_null($min)) {
            return true;
        }
        $f = floatval(($this->_sign ? "-" : "") . $this->_number);
        return (($f >= 0 && $f <= (float)$max) || ($f < 0 && $f >= (float)$min)) ? true : false;
    }

    /**
     * Returns the parsed number as a string.
     */
    public function __toString() {
        if(!$this->_valid) {
            return "";
        }
        $number = $this->_number;
        $fraction = $this->_fraction;
        $len = strlen($number);
        if($len && $len == strspn($number, '0')) {
            $number = "";
            $len = 0;
        }
        $frLen = strlen($fraction);
        if($frLen && $frLen == strspn($fraction, '0')) {
            $fraction = "";
            $frLen = 0;
        }
        if($frLen) {
            $number .= '.' . $this->_fraction;
        }
        elseif(!$len) {
            return '0';
        }
        if($this->_sign) {
            $number = '-' . $number;
        }
        return $number;
    }

    protected function _addThouSep($number, $thouSep = ',') {
        $len = strlen($number);
        if($len < 4) {
            return $number;
        }
        return strrev(implode($thouSep, str_split(strrev($number), 3)));
    }
}
