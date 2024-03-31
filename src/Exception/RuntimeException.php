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
    Description : Exception thrown if an error which can only be found on runtime occurs.
*/
namespace Procomputer\Pcclib\Exception;

/**
 * Exception thrown if an error which can only be found on runtime occurs.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface {
}
