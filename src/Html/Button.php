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
    Description : Builds an HTML button element.
*/
namespace Procomputer\Pcclib\Html;
/**
 * Builds an HTML button element.
 */
class Button extends Common {

    /**
     * __invoke lets you call this object like a function.
     *
     * @param string $innerScript (optional) Script to insert into the button.
     * @param array  $attributes  (optional) Element attributes.
     * @return string|self
     */
    public function __invoke(string $innerScript = null, array $attributes = []) {
        if(null === $innerScript) {
            return $this;
        }
        return $this->render($innerScript, $attributes);
    }

    /**
     * Create an HTML button element.
     * @param string $innerScript (optional) Script to insert into the button.
     * @param array  $attributes  (optional) Element attributes.
     * @return string
     */
    public function render(string $innerScript = '', array $attributes = []): string {
        $element = new Element();
        return $element('button', $innerScript, $attributes, true) ;
    }
}