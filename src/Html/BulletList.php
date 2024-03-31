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
     * Builds an HTML ul, ol list element. __invoke lets you call this object like a function.
     *
     * @param  array|\ArrayObject   $items         Array or array-like object with the elements of the list
     * @param  bool                 $ordered       (optional) Specifies ordered/unordered list; default unordered
     * @param  array|\ArrayObject   $attributes    (optional) Attributes for the ol/ul tag.
     * @param  bool                 $escape        (optional) Escape the items.
     * @throws Exception\InvalidArgumentException
     * @return string|self Returns the list XHTML or $this if items is null.
     */
    public function __invoke(array|\ArrayObject $items = null, bool $ordered = false, array|\ArrayObject $attributes = [], bool $escape = true) {
        if(null === $items) {
            return $this;
        }
        return $this->render($items, $ordered, $attributes, $escape);
    }

    /**
     * Builds an HTML ul, ol list element.
     *
     * @param  array|\ArrayObject   $items         Array or array-like object with the elements of the list
     * @param  bool                 $ordered       (optional) Specifies ordered/unordered list; default unordered
     * @param  array|\ArrayObject   $attributes    (optional) Attributes for the ol/ul tag.
     * @param  bool                 $escape        (optional) Escape the items.
     * @throws Exception\InvalidArgumentException
     * @return string The list XHTML.
     */
    public function render(array|\ArrayObject $items = [], bool $ordered = false, array|\ArrayObject $attributes = [], bool $escape = true): string {

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
    protected function _recurse(array|\ArrayObject $items, string $tag, string $attr, string $liAttributes, bool $escape, int $offset = 0): string {
        $a = (array)$items;
        if(empty($a)) {
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