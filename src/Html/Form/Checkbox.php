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
    Description : Builds an HTML checkbox input element.
*/
namespace Procomputer\Pcclib\Html\Form;

use Procomputer\Pcclib\Html\Element;
use Procomputer\Pcclib\Types;

/**
 * Builds an HTML checkbox input element.
 */
class Checkbox {

    /**
     * Builds an HTML checkbox input element.
     * @param string  $name    Element name.
     * @param string  $value   (optional) Element value when checked.
     * @param string  $checked (optional) Checked attribute value.
     * @param array   $attr    (optional) Element attributes.
     * @return string|self
     */
    public function __invoke(string $name = null, string|int|float $value = '1', bool $checked = false, array $attr = []): string {
        if(null === $name) {
            return $this;
        }
        return $this->render($name, $value, $checked, $attr);
    }
    
    /**
     * Builds an HTML checkbox input element.
     * @param string  $name    Element name.
     * @param string  $value   (optional) Element value when checked.
     * @param string  $checked (optional) Checked attribute value.
     * @param array   $attr    (optional) Element attributes.
     * @return string
     */
    public function render(string $name, string|int|float $value = '1', bool $checked = false, array $attr = []): string {
        if(Types::isBlank($name)) {
            $var = Types::getVartype($name);
            throw new Exception\InvalidArgumentException("invalid 'name' parameter '{$var}': expecting an element name");
        }
        $attr['name'] = $name;
        $attr['value'] = (string)$value;
        if(! isset($attr['id'])) {
            $attr['id'] = $name . '_id';
        }
        if($checked) {
            $attr['checked'] = 'checked';
        }
        else {
            unset($attr['checked']);
        }
        // Could be type 'radio'
        if(! isset($attr['type'])) {
            $attr['type'] = 'checkbox';
        }
        $element = new Element();
        return $element('input', '', $attr, false);
    }
}