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
    Description : Builds an HTML form element.
*/
namespace Procomputer\Pcclib\Html\Form;

use Procomputer\Pcclib\Html\Element;
use Procomputer\Pcclib\Types;

/**
 * Builds an HTML form element.
 */
class Form {

    /**
     * Create an HTML form element.
     *
     * @param string $name    Form name
     * @param string $action  Form action URL
     * @param array  $attr    (optional) Element attributes.
     * @param string $content (optional) HTML content inside the <form> tags.
     * @return string|self Returns the form HTML or $this if name is null;
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(string $name = null, string $action = null, array $attr = [], string $content = null):string  {
        if(null === $name) {
            return $this;
        }
        return $this->createForm($name, $action, $attr, $content);
    }

    /**
     * Create an HTML form element.
     *
     * @param string $name    Form name
     * @param string $action  Form action URL
     * @param array  $attr    (optional) Element attributes.
     * @param string $content (optional) HTML content inside the <form> tags.
     * @return string Returns the form HTML.
     * @throws Exception\InvalidArgumentException
     */
    public function createForm(string $name, string $action, array $attr = [], string $content = null): string  {
        if(Types::isBlank($name)) {
            $var = Types::getVartype($name);
            throw new Exception\InvalidArgumentException("invalid 'name' parameter '{$var}': expecting a form name");
        }
        $attr['action'] = $action;
        if(is_string($content) && ! strlen(trim($content))) {
            $content = null;
        }
        // element($tag, $innerScript = '', array $attributes = array(), $closeTag = false)
        $element = new Element();
        $form = $element('form', empty($content) ? '' : $content, $attr, true) ;
        if(empty($content)) {
            $form = str_replace('</form>', '', $form);
        }
        return $form;
    }
}