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
    Description : Filesystem helper functions
*/
namespace Procomputer\Pcclib;

class WordProcessor {

    // Patterns to replace inch(') and foot(") abbreviations, for example.
    protected $_findReplace = array();

    protected $_options = array();

    protected $_data = array();

    protected $_commonWords = null ;

    protected $_dictionary = array();

    /**
     * Sets the word data.
     * @param array $data Array of words
     * @return \Application_View_Helper_WordProcessor
     */
    public function setData(array $data = []) {
        $this->_data = $data;
        return $this;
    }

    /**
     * Returns the word data.
     * @return array
     */
    public function getData() {
        return $this->_data;
    }

    /**
     * Sets the find/replace array pairs.
     * @param array $findReplace Array of words
     * @return \Application_View_Helper_WordProcessor
     */
    public function setFindReplace(array $findReplace) {
        $this->_findReplace = $findReplace;
        return $this;
    }

    /**
     * Returns the find/replace array pairs.
     * @return array
     */
    public function getFindReplace() {
        return $this->_findReplace;
    }

    /**
     * Set default option values.
     * @param array $options Options to set
     * @return \Application_View_Helper_WordProcessor
     */
    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    /**
     * Parse a string into words.
     *
     * @param string  $text     Text from which to extract keywords.
     * @param array   $options  (optional) Parse options.
     *
     * @return void
     */
    public function parse($text, $options = null) {

        // Init data store.
        $this->setData();

        if(!is_string($text)) {
            // Oops, not string parameter.
            return $text;
        }

        if(null === $options) {
            $options = $this->_options;
        }

        $defaults = array(
            'max' => -1,
            'lowercase' => false,
            'ucfirst' => false,
            'noDuplicates' => true,
            'delimiter' => ' ',
            'spellCheck' => false
        );
        $extOptions = $this->_extend($defaults, $options, false, true);

        if(!strlen($text = $this->_cleanText($this->_replaceAbbreviations($text, $this->getFindReplace())))) {
            return ;
        }

        if($extOptions['lowercase']) {
            $text = strtolower($text);
        }
        $parts = str_word_count($text, 1);
        if(!empty($parts)) {
            $this->_getCommonWords();
            foreach($parts as $word) {
                if($extOptions['spellCheck'] && isset($this->_dictionary[strtolower($word)])) {
                    $word = $this->_dictionary[strtolower($word)];
                }
                $caseWord = $extOptions['lowercase'] ? $word : strtolower($word);
                if(!ctype_punct($caseWord) && false === array_search($caseWord, $this->_commonWords)) {
                    if($extOptions['ucfirst']) {
                        $word = ucfirst($word);
                    }
                    $this->_data[] = $word;
                    if($extOptions['max'] > 1    && count($this->_data) >= $extOptions['max']) {
                        break;
                    }
                }
            }
        }
        if($extOptions['noDuplicates']) {
            $this->_data = array_values(array_flip(array_combine($this->_data, array_map('strtolower', $this->_data))));
        }
    }

    /**
     * Joins words in the data into a string.
     * @param int   $maxLength  Maximum length of result string.
     * @param array $options    (optional) Options.
     * @return string
     */
    public function toString($maxLength = 255, array $options = array()) {
        $words = $this->_data;
        if(empty($words)) {
            return '';
        }
        $delim = isset($options['delimiter']) ? $options['delimiter'] : ' ';
        $delimLen = strlen($delim);
        $length = 0;
        $index = 0;
        foreach($words as $word) {
            $len = strlen($word) + $delimLen;
            if($length + $len > $maxLength) {
                $words = array_slice($words, 0, $index);
                break;
            }
            $length += $len;
            $index++;
        }
        if(isset($options['lowercase']) && $options['lowercase']) {
            $words = array_map('strtolower', $words);
        }
        if(isset($options['capitalize']) && $options['capitalize']) {
            $words = array_map('ucfirst', $words);
        }
        return implode($delim, $words);
    }

    protected function _cleanText($text) {
        // ____"xw____"
        return preg_replace('/["\']*_[_\'"]+/', ' ', trim(strip_tags($text)));
    }

    /**
     * Replaces abbreviations with their unabbreviated values.
     *
     * @param string $text          Text from which to replace abbreviations.
     * @param array  $findReplace   Find/replace array pairs.
     *
     * @return string
     */
    protected function _replaceAbbreviations($text, array $findReplace) {
        if(!is_string($text)) {
            return $text;
        }
        elseif(!strlen(trim($text))) {
            return '';
        }
        $text = ' ' . $text . ' ';
        foreach($findReplace as $pattern => $replace) {
            $text = preg_replace($pattern, $replace, $text);
        }
        return trim($text);
    }

    protected function _getCommonWords() {
        if(null === $this->_commonWords) {
            $this->_commonWords = include __DIR__ . '/CommonWords.php';
        }
    }

    /**
     * Returns the dictionary.
     * @return string|array
     */
    public function getDictionary() {
        return $this->_dictionary;
    }

    /**
     * Sets the dictionary.
     * @param array $dictionary
     * @return \Application_View_Helper_WordProcessor
     */
    public function addDictionary(array $dictionary) {
        $this->_dictionary = array_merge($this->_dictionary, $dictionary);
        return $this;
    }

    /**
     * Clears the dictionary.
     * @return \Application_View_Helper_WordProcessor
     */
    public function clearDictionary() {
        $this->_dictionary = array();
        return $this;
    }

    /**
     * Extends (folds) option values (optionally CASE-INSENSITIVE) into associated default properties
     *
     * @param array   $default            Defaults name=>value pairs.
     * @param array   $options            Options to extend into defaults.
     * @param boolean $saveCustomOptions  (optional, default=TRUE) When TRUE $options parameter keys not found in the
     *                                    $default are returned in the 'custom' return array key.
     * @param boolean $caseInsensitive    (optional, default=TRUE) When TRUE key case ignored else original key case unchanged.
     *
     * @return array Returns either an array having $options extended int $default or, when $saveCustomOptions
     *               evaluates TRUE a 2=element array('default' => defaultOptions, 'custom' => leftoverOptions)
     */
    protected function _extend(array $default, array $options, $saveCustomOptions = true, $caseInsensitive = true) {
        if(count($options)) {
            if($caseInsensitive) {
                $keys = array_keys($options);
                $optionKeys = array_change_key_case(array_combine($keys, $keys));
            }
            else {
                $optionKeys = $options;
            }
            foreach($default as $key => $val) {
                $optionKey = $caseInsensitive ? strtolower($key) : $key;
                if(isset($optionKeys[$optionKey])) {
                    $k = $caseInsensitive ? $optionKeys[$optionKey] : $optionKey;
                    $val = $options[$k];
                    unset($options[$k]);
                    // Don't copy NULL values.
                    if(!is_null($val)) {
                        $default[$key] = $val;
                    }
                }
            }
        }
        return $saveCustomOptions ? array('default' => $default, 'custom' => $options) : $default;
    }
}