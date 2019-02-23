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
    Description : Truncates a text string for easier viewing.
*/
namespace Procomputer\Pcclib;

/**
 * Truncates a text string for easier viewing.
 */
class TextTruncate {

    const TRUNCATE_DEFAULT = 0;
    const TRUNCATE_START = 1;
    const TRUNCATE_CENTER = 2;
    const TRUNCATE_RETURN_STRING = 4;
    const TRUNCATE_RETURN_ARRAY = 8;

    /**
     * Calls this class directly e.g. $obj = new TextTruncate(); return $obj('long string...');
     * @param string|array $item
     * @return type
     */
    public function __invoke($item = null) {
        if(null !== $item) {
            return $this->truncate($item);
        }
    }

    /**
     * 
     * @param string|array  $items
     * @param int           $maxLen
     * @param int           $options
     * @return type
     */
    public function truncate($items, $maxLen, $options = self::TRUNCATE_DEFAULT) {
        $returnTypes = (self::TRUNCATE_RETURN_STRING | self::TRUNCATE_RETURN_ARRAY);
        $returnType = $options & $returnTypes;
        if(0 == $returnType || $returnTypes == $returnType) {
            $options &= ~$returnTypes;
            $options |= is_array($items) ? self::TRUNCATE_RETURN_ARRAY : self::TRUNCATE_RETURN_STRING;
        }
        $max = (int)$maxLen;
        if(! is_array($items)) {
            $str = (string)$items;
            if($max < 1 || ! strlen(trim($str)) || strlen($str) <= $max) {
                return ($options & self::TRUNCATE_RETURN_STRING) ? $str : array($str);
            }
            $items = explode(' ', $str);
        }
        if($max > 0) {
            $keys = array_keys($items);
            while(1) {
                $count = count($items);
                if($count < 2) {
                    break;
                }
                if(strlen(implode(' ', $items)) <= $max) {
                    break;
                }
                if($options & self::TRUNCATE_START) {
                    // Truncate START
                    array_shift($items);
                    array_shift($keys);
                }
                elseif($options & self::TRUNCATE_CENTER) {
                    // Truncate CENTER
                    $center = (int)$count / 2;
                    $key = $keys[$center];
                    unset($items[$key], $keys[$center]);
                }
                else {
                    // Truncate END
                    array_pop($items);
                    array_pop($keys);
                }
            }
            if(1 === count($items)) {
                $text = $this->_truncateStr(reset($items), $max, $options);
                $items = array($text);
            }
        }
        $return = ($options & self::TRUNCATE_RETURN_STRING) ? implode(' ', $items) : $items;
        return $return ;
    }

    protected function _truncateStr($text, $max, $options) {
        $len = strlen($text);
        $leftover = $len - $max;
        if($leftover > 0) {
            if($options & self::TRUNCATE_START) {
                // Truncate START
                $text = substr($text, -$max);
            }
            elseif($options & self::TRUNCATE_CENTER) {
            // Truncate CENTER
                $half /= 2;
                $max -= $half;
                $text = substr($text, 0, $half) . substr($text, -$max);
            }
            else {
                // Truncate END
                $text = substr($text, 0, $max);
            }
        }
        return $text;
    }
}