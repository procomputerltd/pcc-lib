<?php
/* 
 * Copyright (C) 2024 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
namespace Procomputer\Pcclib\HtmlTable;

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\Html\Hyperlink;
use Procomputer\Pcclib\Html\Span;
use Procomputer\Pcclib\Html\Div;

class HtmlTableSort {

    protected $_properties = [
        'attribute' => 'data-sort',
        'attributes' => [],
        'column' => 0,
        'current' => 0,
        'descending' => 0
    ];
    
    /**
     * 
     * @param array $options
     */
    public function __construct(array $options = []) {
        foreach($this->_properties as $key => $val) {
            if(isset($options[$key])) {
                $option = $options[$key];
                switch($key) {
                case 'attribute':
                    if(is_string($option) && ! Types::isBlank($option)) {
                        $this->_properties[$key] = trim($option);
                    }
                    break;
                case 'attributes':
                    if(is_array($option)) {
                        $this->_properties[$key] = $option;
                    }
                    break;
                case 'column':
                    $this->_properties[$key] = is_numeric($option) ? intval($option) : 0;
                    break;
                default:
                    $this->_properties[$key] = $option ? 1 : 0;
                }
            }
        }
    }
    
    /**
     * Sets a property value in $this->_properties.
     * @param string $key
     * @param mixed $val
     * @return $this
     * @throws \RuntimeException
     */
    public function __set(string $key, mixed $val) {
        if(strlen($prop = trim($key))) {
            $prop = strtolower($prop);
            if(isset($this->_properties[$prop])) {
                $this->_properties[$prop] = $val;
                return $this;
            }
        }
        $var = Types::getVarType($key);
        $msg = "property '{$var}' not found";
        throw new \RuntimeException($msg);
    }

    /**
     * Returns a property value from $this->_properties.
     * @param string $key
     * @return mixed
     * @throws \RuntimeException
     */
    public function __get(string $key) : mixed {
        if(strlen($prop = trim($key))) {
            $prop = strtolower($prop);
            if(isset($this->_properties[$prop])) {
                return $this->_properties[$prop];
            }
        }
        $var = Types::getVarType($key);
        $msg = "property '{$var}' not found";
        throw new \RuntimeException($msg);
    }

    /**
     * Decodes a sort value previously set in createLinks() into a 3-element array of constituent 
     * parts as follows: [column, current, descending]
     * @return array
     */
    public function decodeSortValue(mixed $sortValue): array {
        $i = 0;
        if(is_numeric($sortValue)) {
            $i = intval($sortValue);
        }
        elseif(is_string($sortValue) && strlen($trimmed = trim($sortValue)) && is_numeric($trimmed)) {
             $i = intval($trimmed);
        }
        // Returns: [column, current, descending]
        return [$i >> 2, $i & 2, $i & 1];    
    }
    
    /**
     * Encodes a sort value from an array having these elements: [column, current, descending]
     * @param array $sortValue
     * @return int
     */
    public function encodeSortValue(int $column, bool $current, bool $descending): int {
        if($column < 1) {
            return 0;
        }
        return ($column << 2) | (($current ? 1 : 0) << 1) | ($descending ? 1 : 0);
    }
    
    /**
     * Applies sorting hyperlinks to HTML table column header labels.
     * 
     * @param array $labels Header labels.
     * @return array
     */
    public function createLinks(array $labels): array {
        $span = new Span();
        $div = new Div();
        $hyperlink = new Hyperlink();
        // Create sort direction arrows for sorted column.
        $arrow = 'border:solid #c0c0c0;border-width:0 .25em .25em 0;display:inline-block;padding:.25em;';
        $arrowUp = $arrow . 'transform:rotate(-135deg)';
        $arrowDn = $arrow . 'transform:rotate(45deg)';
        $attributes = $this->attributes;
        $style = trim($attributes['style'] ?? '');
        if(strlen($style)) {
            $style .= ';';
        }
        $attributes['style'] = $style . 'padding-left:1em;padding-right:1em';
        $column = 1;
        foreach($labels as $key => $label) {
            if($column === $this->column) {
                // Add up/down arrow next to sorted column.
                $descending = $this->descending;
                $current = 1;
                $style = $descending ? $arrowDn : $arrowUp;
            }
            else {
                $current = $descending = 0;
                $style = '';
            }
            $link = $div($label . ' '. $span('', ['style' => $style]));
            // Set: [column, current, descending]
            $attributes[$this->attribute] = $this->encodeSortValue($column, $current, $descending);
            $attributes['id'] = 'id-' . md5('html-table-column-header-' . $column);
            $labels[$key] = $hyperlink('javascript:void(0)', $link, $attributes);
            $column++;
        }
        return $labels;
    }
}