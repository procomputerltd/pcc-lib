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

class TestForm extends TestCommon {

    /**
     * The name of this test class.
     * @var string
     */
    public $name = 'Form';
    
    /**
     * The description of this test class.
     * @var string
     */
    public $description = 'Pcc Html Form element classes tests';
    
    /**
     * Namespace to test.
     * @var string
     */
    protected $_namespace = 'Procomputer\Pcclib\Html\Form';
    
    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions = true;
    
    /**
     * Classes and parameters
     * @var string
     */
    protected $_classes =  [
        // Button __invoke($name, $label = '', array $attr = [])
        'Button' => [
            'parameters' => [
                [
                    "cmdButton", 
                    "I'M A BUTTON", 
                    [
                        'style' => 'background-color:dodgerblue;color:white',
                        'title' => 'Go ahead and CLICK ME. That is what I\'m here for',
                        'onclick' => 'alert("Yep! You clicked a button :)")'
                    ]
                ]
            ],
        ],

        // Checkbox __invoke($name, $value = '1', $checked = false, array $attr = []) {
        'Checkbox' => [
            'parameters' => [
                [
                    "chkCheckbox", 
                    "CHECK! ( ' this.value ', the checkbox ' value ' attribute )", 
                    true,
                    [
                        'style' => 'border:double magenta',
                        'title' => "Title of 'chkCheckbox'",
                        'onclick' => 'alert(this.value)'
                    ],
                ]
            ],
        ],

        // Form __invoke($name = null, $action = null, array $attr = [], $content = null) {
        'Form' => [
            'parameters' => [
                [
                    "frmMyForm", 
                    'javascript:void(0);', 
                    [
                        'style' => ''
                    ],
                      '<input type="text" name="txtMyFormText" size="40" style="border:double red" />' 
                    . '<input type="submit" name="cmdSubmit" value="Submit Button for this Form"' 
                    . ' onclick="alert(\'Button has no effect - action is javascript:void(0);\')" />'
                ]
            ],
        ],

        // Hidden __invoke($name, $value = '', array $attr = []) {
        'Hidden' => [
            'parameters' => [
                [
                    "txtHiddenElement", 
                    "I'M HIDDEN - BUT YOU EXPOSED ME!", 
                    [
                        'style' => 'color:black',
                        'onclick' => 'alert(this.value)'
                    ], 
                ]
            ],
        ],

        // Submit __invoke($name, $label = '', array $attr = []) {
        'Submit' => [
            'parameters' => [
                [
                    "cmdSubmit", 
                    "I'M A SUBMIT BUTTON", 
                    [
                        'style' => 'background-color:green;color:white'
                    ], 
                ]
            ],
        ],
        
    ];
}
