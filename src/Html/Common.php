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
    Description : Common class extended by other Html classes.
*/
namespace Procomputer\Pcclib\Html;

/**
 * Common class extended by other Html classes.
 */
class Common {
    
    /**
     * Build HTML element attribute declarations.
     * @param array $attributes
     * @return string
     */
    protected function _buildAttribs(array $attributes) {
        if(!empty($attributes)) {
            $attr = [];
            foreach($attributes as $t => $v) {
                $trimmed = trim($t);
                if(!strlen($trimmed)) {
                    continue;
                }
                $attr[] = $trimmed . '="' . str_replace('"', '&quot;', $v) . '"';
            }
            if(!empty($attr)) {
                return ' ' . implode(' ', $attr);
            }
        }
        return '';
    }
}

