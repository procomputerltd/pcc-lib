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
    Description : Builds an HTML IMG element.
*/
namespace Procomputer\Pcclib\Html;
/**
 * Builds an HTML IMG element.
 */
class Image extends Common {
    /**
     * Create an HTML img element. __invoke lets you call this object like a function.
     * @param string $imgUrl     (optional) Image URL.
     * @param array  $attributes (optional) Element attributes.
     * @return string|self
     */
    public function __invoke(string $imgUrl = null, array $attributes = []) {
        if(null === $imgUrl) {
            return $this;
        }
        return $this->render($imgUrl, $attributes);
    }

    /**
     * Create an HTML img element.
     * @param string $imgUrl     (optional) Image URL.
     * @param array  $attributes (optional) Element attributes.
     * @return string
     */
    public function render(string $imgUrl = '', array $attributes = []): string {
        $attributes['src'] = $imgUrl;
        $element = new Element();
        return $element('img', '', $attributes, false) ;
    }
}