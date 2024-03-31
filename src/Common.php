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
    Description : Common class extended by other classes.
*/
namespace Procomputer\Pcclib;

/**
 * Common class extended by other classes.
 */
class Common
{
    /**
     * Code of the last error recorded.
     * @var mixed
     */
    protected $_lastErrorCode = null;

    /**
     * Last error message recorded.
     * @var mixed
     */
    protected $_lastErrorMessage = null;

    /**
     * Returns last error message recorded.
     * @return string
     */
    public function getLastErrorMessage() {
        return $this->_lastErrorMessage;
    }

    /**
     * Returns code of the last error recorded.
     * @return mixed
     */
    public function getLastErrorCode() {
        return $this->_lastErrorCode;
    }

}