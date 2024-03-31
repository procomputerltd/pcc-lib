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
    Description : Helper to allow a dev to call a callable function (normally a closure function) and capture
                  an error if one occurs. Use to capture PHP errors/notices and prevent them from being displayed.
*/
namespace Procomputer\Pcclib;

/**
 * Helper to allow a dev to call a callable function (normally a closure function) and capture
 * an error if one occurs. Use to capture PHP errors/notices and prevent them from being displayed.
 * Example:
 *    $phpErrorHandler = new PhpErrorHandler();
 *    $handle = $phpErrorHandler->call(function()use($file, $mode){return fopen($file, $mode);});
 *    if(! $handle) {
 *        $lastError = $phpErrorHandler->getErrorMsg('fopen failed - this is the default message', 'Something went wrong');
 *    }
 */
class PhpErrorHandler {

    public $lastError = null;

    public $lastErrorObj = null;

    public function __construct() {
        $this->_initError();
    }
    private function _initError() {
        $eData = new \stdClass();
        $eData->isError = false;
        $eData->trace = null;
        $eData->msg = $eData->type = $eData->file = $eData->line = $eData->traceString = '';
        $this->lastErrorObj = $eData;
        return $eData;
    }

    /**
     * Calls a callable function (normally a closure function) and captures error if one occurs. Use to capture PHP errors/notices
     * and prevent them from being displayed.
     *
     * @param function $callable     Callable function.
     *
     * @return mixed Returns the result of the call to the callable function.
     *
     * @global string $php_errormsg
     */
    public function call(callable $callable) {
        $eData = $this->_initError();
        $errorHandler = set_error_handler(function ($eType, $eMsg, $eFile, $eLine) use($eData) {
            $eData->isError = true;
            $eData->type = $eType;
            $eData->msg = $eMsg;
            $eData->file = $eFile;
            $eData->line = $eLine;
            // throw new \ErrorException($msg, 0, $type, $file, $line);
        });
        try {
            $res = $callable(); // Call the callable function.
        } catch (\Throwable $exc) {
            $eData->isError = true;
            $eData->type = $exc->getCode();
            $eData->msg = $exc->getMessage();
            $eData->file = $exc->getFile();
            $eData->line = $exc->getLine();
            $eData->trace = $exc->getTrace();
            $eData->traceString = $exc->getTraceAsString();
        }
        finally {
            set_error_handler($errorHandler);
        }
        $this->lastErrorObj = $eData;
        $this->lastError = $eData->msg;
        return $res;
    }

    /**
     * Returns an error message for the last error saved in $this->lastError by call()
     * @param string $defaultMsg  Default message when $this->lastError empty.
     * @param string $prefix      (optional) Message prefix text.
     * @return string Returns the last error message or the default error message.
     */
    public function getErrorObj() {
        return $this->$this->lastErrorObj;
    }

    /**
     * Returns an error message for the last error saved in $this->lastError by call()
     * @param string $defaultMsg  Default message when $this->lastError empty.
     * @param string $prefix      (optional) Message prefix text.
     * @return string Returns the last error message or the default error message.
     */
    public function getErrorMsg($defaultMsg, $prefix = '') {
        $msg = empty($this->lastError) ? $defaultMsg : $this->lastError;
        if(! empty($prefix) && ! empty($msg)) {
            $msg = $prefix . ': ' . $msg;
        }
        return $msg;
    }

    /**
     * Clears the last error.
     * @return $this
     */
    public function clearError() {
        $this->lastError = '';
        $this->lastErrorObj = null;
        return $this;
    }
}
