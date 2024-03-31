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
use Procomputer\Pcclib\Types;
/**
 * Builds an HTML label element.
 */
class Label extends Common {
    /**
     * Creates an HTML label element. __invoke lets you call this object like a function.
     * @param string $name       Element name.
     * @param string $text       (optional) Label text/html.
     * @param array  $attributes (optional) Element attributes.
     * @return string|self
     */
    public function __invoke(string $name = null, string $text = '', array $attributes = []) {
        if(null === $name) {
            return $this;
        }
        return $this->render($name, $text, $attributes);
    }

    /**
     * Creates an HTML label element.
     * @param string $name       Element name.
     * @param string $text       (optional) Label text/html.
     * @param array  $attributes (optional) Element attributes.
     * @return string
     */
    public function render(string $name, string $text = '', array $attributes = []): string {
        if(Types::isBlank($name)) {
            $var = Types::getVartype($name);
            throw new Exception\InvalidArgumentException("invalid 'name' parameter '{$var}': expecting an element name");
        }
        $attributes['name'] = $name;
        if(! isset($attributes['id'])) {
            $attributes['id'] = $name . '_id';
        }
        $element = new Element();
        // element($tag, $innerScript = '', array $attributes = [], $closeTag = false)
        return $element('label', $text, $attributes, true) ;
    }
}