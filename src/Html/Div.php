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
    Description : Builds an HTML division element.
*/
namespace Procomputer\Pcclib\Html;
/**
 * Builds an HTML division element.
 */
class Div extends Common {
    /**
     * Create an HTML division element.
     * __invoke lets you call this object like a function. Also makes compatible with Zend Framework view helper plugin
     *
     * @param string $innerScript Script to insert into the division.
     * @param array  $attributes  (optional) Element attributes.
     *
     * @return string
     */
    public function __invoke(string $innerScript = null, array $attributes = []) {
        if(null === $innerScript) {
            return $this;
        }
        return $this->render($innerScript, $attributes);
    }

    /**
     * Create an HTML division element.
     *
     * @param string $innerScript Script to insert into the division.
     * @param array  $attributes  (optional) Element attributes.
     *
     * @return string
     */
    public function render(string $innerScript = '', array $attributes = []): string {
        $element = new Element();
        return $element('div', $innerScript, $attributes, true) ;
    }
}