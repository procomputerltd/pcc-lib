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

use Procomputer\Pcclib\NumberParser;
use Procomputer\Pcclib\Constant;
use Procomputer\Pcclib\Error;

/**
 * Tests selected PCC libraries by passing valid and invalid parameters to test parameter validation and error handling.
 */
class TestNumberParser extends TestCommon {

    const CODING_FONT_TEMPLATE = '<span style="color:navy;font-family:Anonymous Pro,Inconsolata,Courier New,Monspaced,Monspace;font-weight:bold">%s</span>';
    
    /**
     * The name of this test class.
     * @var string
     */
    public $name = 'NumberParser';
    
    /**
     * The description of this test class.
     * @var string
     */
    public $description = 'Pcc NumberParser class test';
    
    /**
     * Namespace to test.
     * @var string
     */
    protected $_namespace = 'Procomputer\Pcclib';

    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions = true;
    
    /**
     * Tests selected PCC libraries by passing valid and invalid parameters to test parameter validation and error handling.
     * 
     * @param mixed $values  (optional) Values.
     * @param mixed $options (optional) Options. 
     * 
     * @return array Returns an array of TestResult objects.
     */
    public function exec($values = null, $options = null) {

        $lcOptions = (null === $options) ? [] : array_change_key_case((array)$options);
        
        $throwErrors = isset($lcOptions['throwerrors']) ?  (bool)$lcOptions['throwerrors'] : true;
        NumberParser::throwErrors($throwErrors);
        
        $this->_testResults = [];
        
        $numberParser = new NumberParser();
        
        $method = 'parseNumber';

        /**
         * Some arbitrary numbers.
         */
        $numbers = [
            'acos(1.01)' => [acos(1.01), 330], // Test NAN and INF error handling.
            'new \\stdClass()' => [new \stdClass(), null], // Test error handling of invalid object parameter.
            ['number', null], // Test error handling of invalid syntax of parameter.
            '1.99e+309' => [1.99e+309, 330], // Test NAN and INF error handling.
            ['1.99e+309', 330],
            ['0', 5],
            [(float)0, 5],
            ['123.000', 5],
            ['-+', 5],
            ['+-', 5],
            '2.2250738585072014e-308' => [2.2250738585072014e-308, 330],
            '1.7976931348623158e+308'=> [1.7976931348623158e+308, 330],
            '1.7976931348623158e+307'=> [1.7976931348623158e+307, 330],
            '2.2250738585072014e-30' => [2.2250738585072014e-30, 48],
            ['1.99e308', 5],
            ['+999999999999999999999999999.99999999', 5],
            ['-0xabcdef', 5],
            ['0xabcdef', 5],
            ['xabcdef', 5],
            ['123.456', 5],
            ['000.456', 5],
            ['1.2.3', 5],
        ];
        if(is_array($values)) {
            $numbers = array_merge($values, $numbers);
        }
        
        foreach($numbers as $label => $values) {
            list($num, $dec) = $values;

            $error = $results = null;
            try {
                $this->_testParser($numberParser, $method, $num, $dec, $label);
            } catch(\Throwable $ex) {
                $error = new Error($ex);
            } catch (\Exception $ex) {  // @TODO clean up once PHP 7 requirement is enforced
                $error = new Error($ex);
            }
            if(null !== $error) {
                if(is_int($label)) {
                    $params = $label;
                }
                elseif(is_scalar($num)) {
                    $params = (string)$num;
                }
                else {
                    $params = gettype($num);
                }
                $this->_testResults[] = new TestResult(get_class($numberParser), $method, $params, false, $results, null, $error, true, true);
            }
        }
        
        return $this->_testResults;
    }
    
    protected function _testParser(NumberParser $numberParser, $method, $num, $dec, $label) {        
            
        $parseResult = $numberParser->$method($num);

        $param = is_int($label) ? (string)$num : $label;
        
        $type = gettype($num);
        $strNum = is_string($num) ? "'{$num}'" : (is_int($label) ? (string)$num : $label);
        $numAsString = " ({$type}) {$strNum}";
        $successStr = (true === $parseResult) ? '' : '<span style="color:red">(NOT A NUMBER!)</span>';
        $scripts = [];
        $scripts[] = "<b>{$numAsString}</b> {$successStr}";
        $scripts[] = "<div style=\"margin-left:2em\">\n";
        if(true === $parseResult) {
            $success = true;
            $error = null;
            $scripts[] = $this->_formatNumber($numberParser, $dec);
        }
        else {
            $success = false;
            if($parseResult instanceof Error) {
                $error = $parseResult;
                $scripts[] = "{$strNum} is not a number: {$msg}";
            }
            else {
                // $scripts[] = $this->_formatNumber($numberParser, $dec);
                $success = false;
                $error = null;
                switch($numberParser->getLastErrorCode()) {
                case Constant::E_NO_NUMBER_PARSED:
                    $msg = "no number was parsed - use method '{$method}(\$number)' or specify a number in the caseructor.";
                    break;
                case Constant::E_PARSENUMBER_EMPTY:
                    $msg = "the parameter that specifies the number is empty.";
                    break;
                case Constant::E_PARSENUMBER_NAN:
                    $msg = "the parameter that specifies the number is not-a-number (NAN).";
                    break;
                case Constant::E_PARSENUMBER_INF:
                    $msg = "the parameter that specifies the number is not a number, infinite (INF).";
                    break;
                default: // E_PARSENUMBER_SYNTAX:
                    $msg = "the parameter that specifies the number is not a number, invalid syntax.";
                    break;
                }
                $scripts[] = sprintf(self::CODING_FONT_TEMPLATE, "{$method}({$param})") . "- {$msg}";
            }
        }
        $scripts[] = "</div>\n";
        $results = implode("\n", $scripts);

        $this->_testResults[] = new TestResult(get_class($numberParser), $method, $param, $success, $results, null, $error, false, false);
        
        return $success;
    }
    
    protected function _getPropertiesHtml($numberParser) {
        $properties = [
            'valid',
            'number',
            'fraction',
            'sign',
            'integer',
        ];
        $scripts = [];
        foreach($properties as $p) {
            $val = $numberParser->$p;
            switch($p) {
            case 'valid':
            case 'sign':
            case 'integer':
                $propVal =  $val ? 'TRUE' : 'FALSE';
                break;
            case 'number':
                $type = gettype($val);
                $propVal = "({$type}) $val";
                if(is_string($val) && strlen($val)) {
                    $propVal .= ' (0x' . dechex($val) . ')';
                }
                break;
            default:
                // 'fraction'
                $type = gettype($val);
                $propVal = "({$type}) $val";
                break;
            }
            $scripts[] = "{$p} = {$propVal} <br />\n";
        }
        return implode("\n", $scripts);
    }
    
    protected function _formatNumber($numberParser, $decimals = null, $decimalPoint = '.', $thousandSep = ',') {
        $result = $numberParser->formatNumber($decimals, $decimalPoint, $thousandSep);
        $parameters = [
            (null === $decimals) ? 'NULL' : $decimals,
            (null === $decimalPoint) ? 'NULL' : ("' " . $decimalPoint . " '"),
            (null === $thousandSep) ? 'NULL' : ("' " . $thousandSep . " '"),
        ];
        $paramStr = implode(', ', $parameters);
        $funcPrototype = sprintf(self::CODING_FONT_TEMPLATE, "formatNumber({$paramStr})");
        return "<div style=\"word-wrap:break-word;\">{$funcPrototype} = {$result}</div>";
    }
}
