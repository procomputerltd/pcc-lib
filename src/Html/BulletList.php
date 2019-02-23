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
    Description : Builds an HTML ul, ol list element.
*/
namespace Procomputer\Pcclib\Html;

/**
 * Builds an HTML ul, ol list element.
 */
class BulletList extends Common {

    const TAB_SPACES = 4;
    
    /**
     * __invoke lets you call this object like a function. Also makes compatible with Zend Framework view helper plugin
     */
    public function __invoke($items = null, $ordered = false, $attributes = [], $escape = true) {
        if(null === $items) {
            return $this;
        }
        return $this->render($items, $ordered, $attributes, $escape);
    }
    
    /**
     * Builds an HTML ul, ol list element.
     *
     * @param  array|\Traversable   $items         Array or array-like object with the elements of the list
     * @param  bool                 $ordered       (optional) Specifies ordered/unordered list; default unordered
     * @param  array|\Traversable   $attributes    (optional) Attributes for the ol/ul tag.
     * @param  bool                 $escape        (optional) Escape the items.
     * @throws Exception\InvalidArgumentException
     * @return string The list XHTML.
     */
    public function render($items = [], $ordered = false, $attributes = [], $escape = true) {
        
        $tag = ($ordered) ? 'ol' : 'ul';
        $attr = (array)$attributes;
        if(isset($attr['liAttributes'])) {
            $liAttr = is_array($attr['liAttributes']) ? $attr['liAttributes'] : null;
            unset($attr['liAttributes']);
            $liAttributes = $this->_buildAttribs($liAttr);
        }
        else {
            $liAttributes = '';
        }
        
        return $this->_recurse($items, $tag, $this->_buildAttribs($attr), $liAttributes, $escape);
    }
    
    /**
     */
    protected function _recurse($items, $tag, $attr, $liAttributes, $escape, $offset = 0) {
        if($items instanceof \Traversable) {
            /* @var $items \ArrayObject */
            $empty = ! $items->count();
        }
        elseif(! is_array($items)) { 
            throw new Exception\InvalidArgumentException('Invalid items parameter: expecting array or Traversable in ' . __METHOD__);
        }
        else {
            $empty = empty($items);
        }
        if($empty) {
            throw new Exception\InvalidArgumentException('items parameter may not be empty in ' . __METHOD__);
        }

        $tabSpaces = str_repeat(' ', self::TAB_SPACES);
        $indent = str_repeat($tabSpaces, $offset);
        $indent2 = $indent . $tabSpaces;
        $list = [];
        $flag = $first = false;
        foreach($items as $item) {
            if(is_array($item)) {
                $list[] = $this->_recurse($item, $tag, $attr, $liAttributes, $escape, $offset + 1);
            } 
            elseif(null !== $item) {
                if($flag) {
                    $list[] = $indent2 . '</li>';
                }
                if($escape) {
                    $item = $this->_escape($item);
                }
                $list[] = $indent2 . "<li{$liAttributes}>{$item}";
                $flag = true;
            }
        }
        if($flag) {
            $list[] = $indent2 . '</li>';
        }

        return $indent . '<' . $tag . $attr . '>' . PHP_EOL 
            . $indent . implode(PHP_EOL, $list ) . PHP_EOL 
            . $indent . '</' . $tag . '>' ;
    }
    
    protected function _escape($item) {
        return htmlentities($item);
    }
}
