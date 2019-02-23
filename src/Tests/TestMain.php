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
    Description : Tests selected PCC libraries by passing valid and invalid parameters to test parameter validation and error handling.
*/
namespace Procomputer\Pcclib\Tests;

/**
 * Tests selected PCC libraries by passing valid and invalid parameters to test parameter validation and error handling.
 */
class TestMain extends TestCommon {

    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions = true;
    
    public function getTestClasses() {
        return [
            'TestFilesystem',
            'TestForm',
            'TestHtml',
            'TestImage',
            'TestNumberParser',
        ];
    }

    /**
     * Tests selected PCC libraries by passing valid and invalid parameters to test parameter validation and error handling.
     * 
     * @param mixed $values  (optional) Values.
     * @param mixed $options (optional) Options. 
     * 
     * @return array Returns an array of TestResult objects.
     */
    public function exec($values = null, $options = null) {
        
        foreach($this->getTestClasses() as $testClass) {
            $object = new $testClass();
            $results = $object->exec($values, $options);
            foreach((array)$results as $testResult) {
                $this->_testResults[] = $testResult;
            }
        }
        return $this->_testResults;
    }
    
    /*
     * Test for class 'UrlEmailParser'
     *
     */
    protected function _testUrlEmailParser($values = null, $options = null) {
        // ToDo: Write test code for class 'UrlEmailParser'
    }

    /*
     * Test for class 'Url'
     *
     */
    protected function _testUrl($values = null, $options = null) {
        // ToDo: Write test code for class 'Url'
    }

    /*
     * Test for class 'TextTruncate'
     *
     */
    protected function _testTextTruncate($values = null, $options = null) {
        // ToDo: Write test code for class 'TextTruncate'
    }

    /*
     * Test for class 'File'
     *
     */
    protected function _testFile($values = null, $options = null) {
        // ToDo: Write test code for class 'File'
    }

    /*
     * Test for class 'FileInformation'
     *
     */
    protected function _testFileInformation($values = null, $options = null) {
        // ToDo: Write test code for class 'FileInformation'
    }

    /*
     * Test for class 'IniParser'
     *
     */
    protected function _testIniParser($values = null, $options = null) {
        // ToDo: Write test code for class 'IniParser'
        
    }

    /*
     * Test for class 'PathInfo'
     *
     */
    protected function _testPathInfo($values = null, $options = null) {
        // ToDo: Write test code for class 'PathInfo'
    }

    /*
     * Test for class 'System'
     *
     */
    protected function _testSystem($values = null, $options = null) {
        // ToDo: Write test code for class 'System'
    }

    /*
     * Test for class 'TextParser'
     *
     */
    protected function _testTextParser($values = null, $options = null) {
        // ToDo: Write test code for class 'TextParser'
    }

    /*
     * Test for class 'Types'
     *
     */
    protected function _testTypes($values = null, $options = null) {
        // ToDo: Write test code for class 'Types'
    }

}
