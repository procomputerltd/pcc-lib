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
    Description : Builds an HTML element.
*/
namespace Procomputer\Pcclib\Html;

use Procomputer\Pcclib\Types;
/**
 * Builds an HTML element.
 */
class Element extends Common {

    /**
     * __invoke lets you call this object like a function. Also makes compatible with Zend Framework view helper plugin
     */
    public function __invoke($tag = null, $innerScript = '', array $attributes = [], $closeTag = false) {
        if(null === $tag) {
            return $this;
        }
        return $this->render($tag, $innerScript, $attributes, $closeTag);
    }
    
    /**
     * Create an HTML element.
     * @param string  $tag          Element tag.
     * @param string  $innerScript  (optional) Division inner script (NOTE: htmlentities() is not applied.)
     * @param array   $attributes   (optional) Element attributes.
     * @param boolean $closeTag     (optional) When TRUE element closing tag (eg </a>) is appended.
     * @return string
     */
    public function render($tag, $innerScript = '', array $attributes = [], $closeTag = false) {
        if(!is_string($tag) || Types::isBlank($tag)) {
            $var = Types::getVartype($tag);
            throw new Exception\InvalidArgumentException("invalid 'tag' parameter '{$var}': expecting a string HTML tag");
        }
        $return = array('<' . $tag);
        $atrribs = $this->_buildAttribs($attributes);
        if(!empty($atrribs)) {
            $return[] = $atrribs;
        }
        if($closeTag) {
            $return[] = '>' . $innerScript . '</' . $tag . '>';
        }
        else {
            $return[] = ' />';
        }
        return implode('', $return);
    }

}
