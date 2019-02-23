<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Jan 01, 2016, 12:00:00 PM PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : Static helper methods for arrays.
*/
namespace Procomputer\Pcclib;

/**
 * Static helper methods for arrays.
 */
class Arrays {
    
    /**
     * Extends option values into associated default properties
     * 
     * @param array   $defaults         Defaults; initial name=>value pairs.
     * @param array   $options          Option key=>value pairs to extend (fold) into the Defaults.
     * @param boolean $preserveOptions (optional, default=TRUE) When FALSE original options are unset
     * @param boolean $caseInsensitive (optional, default=TRUE) When FALSE original key case unchanged.
     * @param boolean $omitNull        (optional, default=FALSE) When TRUE keys with NULL value are omitted.
     *
     * @return array
     */
    public static function extend(array $defaults, array &$options, $preserveOptions = true, $caseInsensitive = true,
        $omitNull = false) {
        $keys = array_keys($options);
        $optionKeys = array_combine($keys, $keys);
        if($caseInsensitive) {
            $optionKeys = array_change_key_case($optionKeys);
        }
        foreach($defaults as $key => $val) {
            $optionKey = $caseInsensitive ? strtolower($key) : $key;
            if(isset($optionKeys[$optionKey])) {
                $k = $optionKeys[$optionKey];
                if(isset($options[$k])) {
                    $val = $options[$k];
                    if(!$preserveOptions) {
                        unset($options[$k]);
                    }
                    if(! $omitNull || !is_null($val)) {
                        $defaults[$key] = $val;
                    }
                }
            }
        }
        return $defaults;
    }

    /**
     * Extends and merges 2 arrays and returns the resulting array.
     * 
     * @param array   $array1          Defaults; initial array of name=>value pairs.
     * @param array   $array2          Array to merge with the first array.
     * @param boolean $caseInsensitive (optional, default=TRUE) When FALSE original key case unchanged.
     * @param boolean $omitNull        (optional, default=FALSE) When TRUE keys with NULL value are omitted.
     *
     * @return array
     */
    public static function arrayMerge(array $array1, array $array2, $caseInsensitive = true, $omitNull = false) {
        $extended = self::extend($array1, $array2, false, $caseInsensitive, $omitNull);
        if($caseInsensitive) {
            $keys = array_keys($extended);
            $optionKeys = array_change_key_case(array_combine($keys, $keys));
            foreach($array2 as $name => $val) {
                if(! $omitNull || !is_null($val)) {
                    $key = strtolower($name);
                    $k = isset($optionKeys[$key]) ? $optionKeys[$key] : $name;
                    $extended[$k] = $val;
                }
            }
            return $extended;
        }
        foreach($array2 as $name => $val) {
            if(! $omitNull || !is_null($val)) {
                $extended[$name] = $val;
            }
        }
        return $extended;
    }

    /**
     * Attempts to convert the mixed parameter to an array. Returns the default if mixed parameter cannot be converted.
     * @param mixed $mixed   Variable to convert to array.
     * @param mixed $default Value returned when $mixed cannot be converted.
     * @return array|mixed
     */
    public static function toArray($mixed, $default = []) {
        if(is_array($mixed)) {
            return $mixed;
        }
        if(null === $mixed) {
            return $default;
        }
        // Check for objects such as Zend\Config\Config that have 'toArray()' method.
        if(is_object($mixed) && method_exists($mixed, 'toArray')) {
            return $mixed->toArray();
        }
        if($mixed instanceof Traversable) {
            return (array)$mixed;
        }
        return $default;
    }

    
}
