<?php
namespace Procomputer\Pcclib\Html;
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
/**
 * Builds an HTML select element.
 */
use Procomputer\Pcclib\Html\Element;

class Select extends Common {
    
    /**
     * __invoke lets you call this object like a function.
     *
     * @param array $values
     * @param array $attributes
     * @param array $options
     * @return string|$this|\self
     */
    public function __invoke(?array $values = null, array $attributes = [], array $options = []): string|Select {
        if(null === $values) {
            return $this;
        }
        return $this->render($values, $attributes, $options);
    }

    /**
     * Renders the SELECT html element.
     *
     * @param array $values
     * @param array $attributes
     * @param array $options
     * @return string
     */
    public function render(array $values, array $attributes = [], array $options = []): string {
        $selected = $options['selected'] ?? null; 
        if($selected) {
            $selected = is_array($selected) ? $selected : [$selected => $selected];
        }
        else {
            $selected = [];
        }
        if(empty($selected)) {
            $default = $options['default'] ?? null;
            if(null !== $default) {
                if(is_array($default)) {
                    $default = reset($default);
                }
                if(is_scalar($default) && ! is_bool($default)) {
                    $default = strval($default);
                    $selected = [$default => $default];
                }
            }
        }
        $indent = isset($options['indent'])
            ? (is_numeric($options['indent']) ? str_repeat("\t", intval($options['indent'])) : $options['indent'])
            : '';
        $optionIndent = "\t" . $indent;
        $element = new Element();
        $selectOptions = [];
        foreach($values as $value => $label) {
            $optionSttributes = (false !== array_search($value, $selected)) ? ['selected' => 'true'] : [];
            $optionSttributes['value'] = $value;
            $selectOptions[] = $optionIndent . $element->render('option', $label, $optionSttributes, true);
        }
        
        $html = $indent . $element->render('select', "\n" . implode("\n", $selectOptions), $attributes, true);
        return $html;
    }
}