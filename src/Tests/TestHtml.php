<?php

/**
 * ASCII extended chars 128-255
 * 
 * ÇüéâäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜø£Ø×ƒáíóúñÑªº¿®¬½¼¡«»░▒▓│┤ÁÂÀ©╣║╗╝¢¥┐└
 * ┴┬├─┼ãÃ╚╔╩╦╠═╬¤ðÐÊËÈıÍÎÏ┘┌█▄¦Ì▀ÓßÔÒõÕµþÞÚÛÙýÝ¯´≡±‗¾¶§÷¸°¨·¹³²■nbsp
 */

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
    Description : Tests HTML classes.
*/
namespace Procomputer\Pcclib\Tests;

/**
 * Tests HTML classes.
 */
class TestHtml extends TestCommon {

    /**
     * The name of this test class.
     * @var string
     */
    public $name = 'Html';
    
    /**
     * The description of this test class.
     * @var string
     */
    public $description = 'Pcc Html element classes tests';
    
    /**
     * Namespace to test.
     * @var string
     */
    protected $_namespace = 'Procomputer\Pcclib\Html';

    /**
     * Whether this test class and associated function throw exceptions.
     * @var boolean
     */
    protected $_throwsExceptions = true;
    
    // ToDo: Write test code for class 'Html'
    protected $_classes =  [
        // Hyperlink __invoke($href, $innerScript, array $attr = []) {
        'Hyperlink' => [
            'parameters' => [
                [
                    '/', 
                    "HELLO From Italic Hyperlink!", 
                    [
                        'style' => 'font-style:italic'
                        ], 
                    true
                ],
            ],
        ],

        // BulletList __invoke($items = null, $ordered = false, array $attributes = [], $escape = true)
        /*  List types:

            // type 
            list-style: square;

            // image 
            list-style: url('../img/shape.png');

            // position 
            list-style: inside;

            // type | position 
            list-style: georgian inside;

            // type | image | position 
            list-style: lower-roman url('../img/shape.png') outside;

            // Keyword value 
            list-style: none;
         */
        'BulletList' => [
            'parameters' => [
                [
                    [
                        'List with custom svg URL bullets, ',
                        'Sibling item',
                        [
                            'Child Item',
                            [
                                'Grand-Child Item',
                                'Some extended ASCII: Ã╚╔╩╦╠═╬¤ðÐÊËÈıÍÎÏ┘┌█▄¦Ì▀ÓßÔÒõÕµþÞÚÛÙýÝ¯´≡±‗¾¶§÷¸°¨·¹³²■nbsp',
                            ],
                        ],
                        'ANother Sibling',
                    ], 
                    false,
                    [
                        'liAttributes' => ['style' => 'color:tomato'], 
                        'style' => 'list-style:url(https://interactive-examples.mdn.mozilla.net/media/examples/rocket.svg)', 
                        'type' => 'disc'
                        ],
                    false, 
                ],
                [
                    [
                        'BulletList with square bullets',
                        [
                            'Child BulletList Item 1',
                            'Child BulletList Item 2',
                        ],
                        'Sibling bulletList Item',
                        'Sibling bulletList Item2',
                        'ASCII extended chars: ÇüéâäàåçêëèïîìÄÅÉæÆôöòûùÿÖÜø£Ø×ƒáíóúñÑªº¿®¬½¼¡«»░▒▓│┤ÁÂÀ©╣║╗╝¢¥┐└',
                    ], 
                    false,
                    [
                        // 'liAttributes' => ['style' => 'color:pink'], 
                        'style' => 'cursor:pointer;color:green;list-style-type:square;', 
                        'type' => 'square'
                        ],
                    true, 
                ],
                // Next set of test params.
                [
                    [
                        'BulletList STENCIL STD font, circle bullets',
                        'Extended ASCII: Ã╚╔╩╦╠═╬¤ðÐÊËÈıÍÎÏ┘┌█▄¦Ì▀ÓßÔÒõÕµþÞÚÛÙýÝ¯´≡±‗¾¶§÷¸°¨·¹³²■nbsp',
                        'BulletList Item ',
                        'BulletList Item ',
                    ], 
                    false,
                    [
                        'liAttributes' => ['style' => 'font-family:stencil std'], 
                        'style' => 'cursor:pointer;color:magenta;list-style-type:circle;', 
                    ],
                    true, 
                ]
                
            ],
        ],
        
        // Div __invoke($innerScript = '', array $attr = array()) {
        'Div' => [
            'parameters' => [
                [
                    "HELLO Div!", 
                    ['style' => 'cursor:pointer;background-color:navy;color:white']
                ]    
            ],
        ],

        // Element __invoke($tag, $innerScript = '', array $attributes = [], $closeTag = false) {
        'Element' => [
            'parameters' => [
                [
                    "div", 
                    "HELLO Element!", 
                    [
                        'style' => 'cursor:pointer;background-color:navy;color:white'
                        ], 
                    true
                ],
            ],
        ],

        // Image __invoke($imgUrl = '', array $attr = []) {
        'Image' => [
            'parameters' => [
                [
                    'https://www.islapedia.com/images/thumb/8/8d/Whistler%27s_Mother.png/400px-Whistler%27s_Mother.png', 
                    [
                        'style' => 'cursor:pointer;width:200px'
                        ], 
                    true
                ],
            ],
        ],
            
        // Label __invoke($name, $text, array $attr = array()) {
        'Label' => [
            'parameters' => [
                [
                    'txtLabelName', 
                    'HELLO Brown Label!',
                    [
                        'style' => 'color:brown'
                        ], 
                    true
                ],
            ],
        ],
            
        // Span __invoke($innerScript = '', array $attr = array()) {
        'Span' => [
            'parameters' => [
                [
                    "HELLO Span!", 
                    ['style' => 'cursor:pointer;background-color:cyan;color:black']
                ],
            ]
        ]
    ];
    
}
