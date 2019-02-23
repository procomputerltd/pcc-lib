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
    
    /**
     * Calls a callable function (normally a closure function) and captures error if one occurs. Use to capture PHP errors/notices
     * and prevent them from being displayed.
     *
     * @param function $callable     Callable function.
     * @param boolean  $recordError  (optional) Record error messages else execute & return result only.
     *
     * @return mixed Returns the result of the call to the callable function.
     *
     * @global string $php_errormsg
     */
    public function call($callable, $recordError = true) {
        global $php_errormsg;
        if($recordError) {
            $php_errormsg = $this->lastError = '';
            if(function_exists('error_clear_last')) {
                error_clear_last();
            }
            else {
                // Set error_get_last value to known state,
                set_error_handler('var_dump', 0);
                @$ak9ikKjt6U7; // Uninitialized variable.
                restore_error_handler();                 
                $lastError = error_get_last();
            }
        }
        $track = ini_set('track_errors', 1);   // Copy error message to $php_errormsg.
        $display = ini_set('display_errors', 0); // Don't display errors.
        $res = $callable(); // Call the callable function.
        ini_set('display_errors', $display);// Restore original error reporting, tracking, display.
        ini_set('track_errors', $track);
        // Don't set last error if function already set it.
        if($recordError && empty($this->lastError)) {
            $msg = $php_errormsg;
            if(false === $res) {
                if(empty($msg)) {
                    /* error_get_last() return array like:
                        [type] => 8
                        [message] => Undefined variable: a
                        [file] => C:\WWW\index.php
                        [line] => 2
                     */
                    $lastError = error_get_last();
                    if(is_array($lastError) && isset($lastError['message'])) {
                        $msg = $lastError['message'];
                        if(false !== strpos($msg, 'ak9ikKjt6U7')) {
                            $msg = '';
                        }
                    }
                    else {
                        $msg = '';
                    }
                }
                $this->lastError = $msg;
            }
            elseif(! empty($msg)) {
                $this->lastError = $msg;
                /* error_get_last() return array like:
                    [type] => 8
                    [message] => Undefined variable: a
                    [file] => C:\WWW\index.php
                    [line] => 2
                 */
            }
        }
        return $res;
    }
    
    /**
     * Returns an error message for the last error saved in $this->lastError by call()
     * @param string $defaultMsg  Default message when $this->lastError empty.
     * @param string $prefix      (optional) Message prefix text.
     * @return string Returns the last error message or the default error message.
     */
    public function getErrorMsg($defaultMsg, $prefix = '') {
        $msg = empty($prefix) ? '' : $prefix;
        $errStr = empty($this->lastError) ? $defaultMsg : $this->lastError;
        if(! empty($msg) && ! empty($errStr)) {
            $msg .= ': ' . $errStr;
        }
        return $msg;
    }

    /**
     * Clears the last error.
     * @return $this
     */
    public function clearError() {
        $this->lastError = '';
        return $this;
    }
}
