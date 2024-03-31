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
    Description : * Builds an HTML hidden input element.
*/
namespace Procomputer\Pcclib\Html\Form;

use Procomputer\Pcclib\Html\Element;
use Procomputer\Pcclib\Types;

/**
 * Builds an HTML hidden input element.
 */
class Hidden {

    /**
     * Builds an HTML hidden input element.
     * @param string  $name   Element name.
     * @param string  $value  (optional) Element value.
     * @param array   $attr   (optional) Element attributes.
     * @return string|self
     */
    public function __invoke(string $name = null, string|int|float $value = '', array $attr = []) {
        if(null === $name) {
            return $this;
        }
        return $this->render($name, $value, $attr);
    }
    
    /**
     * Builds an HTML hidden input element.
     * @param string  $name   Element name.
     * @param string  $value  (optional) Element value.
     * @param array   $attr   (optional) Element attributes.
     * @return string
     */
    public function render(string $name, string|int|float $value = '', array $attr = []):string {
        if(Types::isBlank($name)) {
            $var = Types::getVartype($name);
            throw new Exception\InvalidArgumentException("invalid 'name' parameter '{$var}': expecting an element name");
        }
        $attr['type'] = 'hidden';
        $attr['name'] = $name;
        $attr['value'] = (string)$value;
        $element = new Element();
        return $element('input', '', $attr, false);
    }

}
