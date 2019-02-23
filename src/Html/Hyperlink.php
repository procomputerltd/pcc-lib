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
    Description : Builds an HTML hyperlink (aka anchor) element.
*/
namespace Procomputer\Pcclib\Html;
/**
 * Builds an HTML hyperlink (aka anchor) element.
 */
class Hyperlink extends Common {

    /**
     * __invoke lets you call this object like a function. Also makes compatible with Zend Framework view helper plugin
     */
    public function __invoke($href = null, $innerScript = '', array $attributes = []) {
        if(null === $href) {
            return $this;
        }
        return $this->render($href, $innerScript, $attributes);
    }
    
    /**
     * Create an HTML anchor element.
     * @param string  $href         Value for 'href' attribute.
     * @param string  $innerScript  (optional) Anchor inner text value.
     * @param array   $attributes   (optional) Element attributes.
     * @return string
     */
    public function render($href, $innerScript = '', array $attributes = []) {
        $attributes['href'] = $href;
        $element = new Element();
        return $element('a', $innerScript, $attributes, true);
    }

}
