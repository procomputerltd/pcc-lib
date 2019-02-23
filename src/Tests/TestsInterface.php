<?php

/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Dec 23, 2018, 10:11:28 AM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : Implements tests of library classes.
*/

namespace Procomputer\Pcclib\Tests;

/**
 * Implements tests of library classes.
 */
interface TestsInterface
{
    /**
     * Executes tests of library classes.
     * 
     * @param mixed $values  (optional) Values.
     * @param mixed $options (optional) Options. 
     * 
     * @return array Returns an array of TestResult objects.
     */
    public function exec($values = null, $options = null);
}