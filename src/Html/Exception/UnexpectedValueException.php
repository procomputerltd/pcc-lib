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
    Description : Exception thrown if a value does not match with a set of values.
*/
namespace Procomputer\Pcclib\Html\Exception;

/**
 * Exception thrown if a value does not match with a set of values.
 */
class UnexpectedValueException extends \UnexpectedValueException implements ExceptionInterface {
}
