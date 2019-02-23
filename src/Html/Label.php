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
    Description : * Builds an HTML label element.
*/
namespace Procomputer\Pcclib\Html;
/**
 * Builds an HTML label element.
 */
class Label extends Common {
    /**
     * __invoke lets you call this object like a function. Also makes compatible with Zend Framework view helper plugin
     */
    public function __invoke($name = null, $text = '', array $attributes = []) {
        if(null === $name) {
            return $this;
        }
        return $this->render($name, $text, $attributes);
    }
    
    /**
     * Create an HTML label element.
     * @param string $name Element name.
     * @param string $text Label text/html.
     * @param array  $attributes (optional) Element attributes.
     * @return string
     */
    public function render($name, $text, array $attributes = []) {
        $attributes['name'] = $name;
        if(! isset($attributes['id'])) {
            $attributes['id'] = $name . '_id';
        }
        $element = new Element();
        // element($tag, $innerScript = '', array $attributes = [], $closeTag = false)
        return $element('label', $text, $attributes, true) ;
    }
}