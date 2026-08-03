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
    Description : Common class extended by other Html classes.
*/
namespace Procomputer\Pcclib\Html;

/**
 * Common class extended by other Html classes.
 */
class Common {

    /**
     * Merges element attributes.
     * @param array $attributes
     * @return array
     */
    public function mergeAttributes(array $attributes, ...$mergedAttributes): array {
        foreach($mergedAttributes as $attr) {
            // Reject attributes that are not array.
            if(is_array($attr)) {
                foreach($attr as $k => $v) {
                    // Reject attributes names that are not string.
                    if(is_string($k)) {
                        switch(strtolower($k)) {
                        case 'class':
                        case 'autocomplete':
                            $v = $this->addClass($attributes[$k] ?? '', $v);
                            break;
                        default:
                        }
                        $attributes[$k] = $v;
                    }
                }
            }
        }
        return $attributes;
    }
    
    /**
     * Build HTML element attribute declarations.
     * @param array $attributes
     * @return string
     */
    public function buildAttribs(array $attributes): string {
        if(!empty($attributes)) {
            $attr = [];
            foreach($attributes as $t => $v) {
                if(! is_string($v)) {
                    $break = 1;
                }
                $trimmed = trim($t);
                if(!strlen($trimmed)) {
                    continue;
                }
                $attr[] = $trimmed . '="' . str_replace('"', '&quot;', $v ?? '') . '"';
            }
            if(!empty($attr)) {
                return ' ' . implode(' ', $attr);
            }
        }
        return '';
    }
    
    /**
     * Adds class specifiers to a class string.
     * @param string       $class Class string to which to add.
     * @param string|array $value Value or values to add.
     * @return string
     */
    public function addClass(string $class, string|array $value) {
        return $this->_addRemoveClass($class, $value, false);
    }
    
    /**
     * Removes class specifiers from a class string.
     * @param string       $class Class string from which to remove.
     * @param string|array $value Value or values to remove.
     * @return string
     */
    public function removeClass(string $class, string|array $value) {
        return $this->_addRemoveClass($class, $value, true);
    }
    
    /**
     * Adds or removes class specifiers to/from a class string.
     * @param string       $class Class string from which to add/remove.
     * @param string|array $value Value or values to add/remove.
     * @param bool         $remove When true the class or classes are removed.
     * @return string
     */
    protected function _addRemoveClass(string $class, string|array $value, bool $remove) {
        $trimmedClass = trim($class);
        $classes = strlen($trimmedClass) ? array_flip(array_flip(preg_split("/\\s+/", $trimmedClass))) : [];
        $arrayVal = (array)$value;
        foreach($arrayVal as $value) {
            $trimmedVal = trim($value);
            if(! strlen($trimmedVal)) {
                continue;
            }
            foreach(preg_split("/\\s+/", $trimmedVal) as $str) {
                $i = array_search($str, $classes);
                $found = (false !== $i);
                if($remove) {
                    if($found) {
                        unset($classes[$i]);
                    }
                }
                elseif(! $found) {
                    $classes[] = $str;
                }
            }
        }
        return implode(' ', $classes);
    }
}

