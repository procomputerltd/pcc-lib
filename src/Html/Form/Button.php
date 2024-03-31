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
    Description : Builds an HTML button input element.
*/
namespace Procomputer\Pcclib\Html\Form;

use Procomputer\Pcclib\Html\Element;
use Procomputer\Pcclib\Types;

/**
 * Builds an HTML button input element.
 */
class Button {

    /**
     * Create an HTML submit element.
     * @param string  $name   Element name.
     * @param string  $label  Element label value.
     * @param array   $attr   (optional) Element attributes.
     * @return string|self
     */
    public function __invoke(string $name = null, string $label = '', array $attr = []) {
        if(null === $name) {
            return $this;
        }
        return $this->render($name, $label, $attr);
    }

    /**
     * Create an HTML submit element.
     * @param string  $name   Element name.
     * @param string  $label  Element label value.
     * @param array   $attr   (optional) Element attributes.
     * @return string
     */
    public function render(string $name, string $label = '', array $attr = []) {
        if(Types::isBlank($name)) {
            $var = Types::getVartype($name);
            throw new Exception\InvalidArgumentException("invalid 'name' parameter '{$var}': expecting an element name");
        }
        $attr['type'] = 'button';
        $attr['name'] = $name;
        $attr['value'] = (string)$label;
        if(! isset($attr['id'])) {
            $attr['id'] = $name . '_id';
        }
        $element = new Element();
        return $element('input', '', $attr, false);
    }
}
