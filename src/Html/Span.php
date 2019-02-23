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
    Description : Builds an HTML span element.
*/
namespace Procomputer\Pcclib\Html;
/**
 * Builds an HTML span element.
 */
class Span extends Common {
    
    /**
     * __invoke lets you call this object like a function. Also makes compatible with Zend Framework view helper plugin
     * 
     * @param string $innerScript
     * @param array  $attributes
     * 
     * @return $this|string
     */
    public function __invoke($innerScript = null, array $attributes = []) {
        if(null === $innerScript) {
            return $this;
        }
        return $this->render($innerScript, $attributes);
    }
    
    /**
     * Create an HTML span element.
     * @param string $innerScript (optional) Script to insert into the span.
     * @param array  $attributes        (optional) Element attributes.
     * @return string
     */
    public function render($innerScript = '', array $attributes = []) {
        $element = new Element();
        return $element('span', $innerScript, $attributes, true) ;
    }
}