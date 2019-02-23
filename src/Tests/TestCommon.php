<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Nov 18, 2018, 9:11:55 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : PHP Software by Pro Computer 
*/
namespace Procomputer\Pcclib\Tests;

use Procomputer\Pcclib\Error;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Types;

abstract class TestCommon implements TestsInterface {

    /**
     * The name of this test class.
     * @var string
     */
    public $name;
    
    /**
     * The description of this test class.
     * @var string
     */
    public $description;
    
    /**
     * Namespace to test.
     * @var string
     */
    protected $_namespace;
    
    /**
     * Class and parameter declarations.
     * @var array
     */
    protected $_classes;
    
    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions;
    
    /**
     * Test results storage.
     * @var array
     */
    protected $_testResults = [];

    /**
     * The last value(s) returned by call to _callStatic()
     * @var mixed
     */
    protected $_lastCallStaticResult = null;
    
    /**
     * Tests class methods.
     * 
     * @param mixed $values  (optional) Values.
     * @param mixed $options (optional) Options. 
     * 
     * @return array Returns an array of TestResult objects.
     */
    public function exec($values = null, $options = null) {
        foreach($this->_classes as $class => $paramLists) {
            $classPath = $this->_namespace . '\\' . $class;
            foreach($paramLists as $paramList) {
                foreach($paramList as $params) {
                    $object = new $classPath();
                    $this->_testResults[] = $this->_objectInvoke($object, $class, $this->_namespace, '__invoke', $params);
                }
            }
       }
       return $this->_testResults;
    }
    
    /**
     * Calls an object method for testing.
     * 
     * @param mixed             $object     Object class to test.
     * @param string            $class      Class name
     * @param string            $namespace  Class namespace name
     * @param string            $method     Class method name.
     * @param array|ArrayObject $params    (optional) Custom parameters passed to the object method.
     * 
     * @return \Procomputer\Pcclib\Tests\TestResult
     */
    protected function _objectInvoke($object, $class, $namespace, $method = '__invoke', $params = null) {
        
        $classPath = $namespace . '\\' . $class;
        
        $reflection = new \ReflectionMethod($classPath, $method);
        
        $params = $this->_resolveParameters($reflection, $params);
        $threw = false;
        $result = '';
        try {
            $result = call_user_func_array($object, $params);
            if(null === $result || is_string($result) && ! strlen($result)) {
                $res = (null === $result) ? "NULL" : "EMPTY";
                $result = "ERROR: {$class} returned a {$res} result";
            }
            $error = null;
        } catch (\Throwable $ex) {
            $error = new Error($ex);
            $threw = true;
        } catch (\Exception $ex) {
            $threw = true;
            $error = new Error($ex);
        }
        return new TestResult($classPath, $method, $params, null === $error, $result, 'html', $error, $this->_throwsExceptions, $threw);
    }
    
    /**
     * Calls a static function to be tested.
     * @param string          $classPath
     * @param string          $method
     * @param array           $params
     * @param PhpErrorHandler $phpErrHandler
     */
    protected function _callStatic($classPath, $method, array $params, PhpErrorHandler $phpErrHandler, $throwsExceptions = false) {
        
        $this->_lastCallStaticResult = null;
        
        $callback = $classPath . '::' . $method;

        $res = $phpErrHandler->call(function()use($callback, $params){
            return call_user_func_array($callback, $params);
        });
        
        $this->_lastCallStaticResult = $res;
        
        if($res instanceof Error) {
            /* @var $res Procomputer\Error */
            $error = $res;
            $results = "";
            $success = false;
        }
        else {
            $var = Types::getVartype($res);
            $results = $var;
            $error = null;
            $success = true;
        }
        $this->_testResults[] = new TestResult($classPath, $method, $params, $success, $results, null, $error, $throwsExceptions);
    }
    
    /**
     * 
     * @param \ReflectionMethod $reflection
     * @param mixed $customParams Array of custom parameteres or a closure from which to get custom parameters.
     * 
     * @return array
     */
    protected function _resolveParameters(\ReflectionMethod $reflection, $customParams) {
        
        $reflectionParams = $reflection->getParameters();        
        $paramCount = count($reflectionParams);
        if(! $paramCount) {
            return [];
        }
        
        /* @var $param ReflectionParameter */
        $index = 0;
        $c = count($customParams);
        if($c) {
            $customParam = reset($customParams);
        }
        
        /* @var $param ReflectionParameter */
        foreach($reflectionParams as $param) {
            if($index < $c) {
                $value = $customParam;
            }
            elseif($param->isDefaultValueAvailable()) {
                $value = $param->getDefaultValue();
                if(is_string($value) && ! strlen($value)) {
                    $value = 'Parameter' . $index;
                }
            }
            else {
                $type = $param->getType();
                if($param->isArray()) {
                    $type = 'array';
                }
                switch($type) {
                    case 'array':
                        $value = ['a' => '1'];
                        break;
                    case 'int':
                    case 'float':
                    case 'double':
                        $value = 1;
                        break;
                    case 'boolean':
                        $value = 1;
                        break;
                    default: // case 'string':
                        $value = 'string' . strval($index);
                }
            }
            $params[] = $value;
            
            $index++;
            if($index < $c) {
                $customParam = next($customParams);
            }
        }
        return $params;
    }
    
    /**
     * Returns the results of this test session.
     * @return array
     */
    public function getTestResults() {
        return $this->_testResults;
    }
}
