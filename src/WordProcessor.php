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

    const OPTION_CAPITALIZE    = 'capitalize';
    const OPTION_DELIMITER     = 'delimiter';
    const OPTION_LOWERCASE     = 'lowercase';
    const OPTION_MAX           = 'max';           // Maximum length of combined words.
    const OPTION_MAXWORDLENGTH = 'maxwordlength';
    const OPTION_NODUPLICATES  = 'noduplicates';
    const OPTION_SPELLCHECK    = 'spellcheck';
    const OPTION_DO_NOT_FILTER = 'nofilterchars';

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
    public function parse($text, array $options = []) {

        // Init data store.
        $this->setData();

        if(!is_string($text)) {
            // Oops, not string parameter.
            return $text;
        }

        if(null === $options) {
            $options = $this->_options;
        }
        $extOptions = $this->_extendDefaults($options);
        if(!strlen($text = $this->_cleanText($this->_replaceAbbreviations($text, $this->getFindReplace())))) {
            return ;
        }

        if($extOptions[self::OPTION_LOWERCASE]) {
            $text = strtolower($text);
        }
        $parts = str_word_count($text, 1);
        if(!empty($parts)) {
            $this->_getCommonWords();
            $maxWords = is_numeric($extOptions[self::OPTION_MAX]) ? intval($extOptions[self::OPTION_MAX]) : -1;
            foreach($parts as $word) {
                if($extOptions[self::OPTION_SPELLCHECK] && isset($this->_dictionary[strtolower($word)])) {
                    $word = $this->_dictionary[strtolower($word)];
                }
                $caseWord = $extOptions[self::OPTION_LOWERCASE] ? $word : strtolower($word);
                if(!ctype_punct($caseWord) && false === array_search($caseWord, $this->_commonWords)) {
                    if($extOptions[self::OPTION_CAPITALIZE]) {
                        $word = ucfirst($word);
                    }
                    $this->_data[] = $word;
                    if($maxWords > 1 && count($this->_data) >= $maxWords) {
                        break;
                    }
                }
            }
        }
        if($extOptions[self::OPTION_NODUPLICATES]) {
            $this->_data = array_values(array_flip(array_combine($this->_data, array_map('strtolower', $this->_data))));
        }
    }

    /**
     * Returns array of words with options applied.
     * @param array $options    (optional) Options.
     * @return array
     */
    public function toArray(array $options = []) {
        return $this->_getArray($this->_data, $this->_extendDefaults($options));
    }

    /**
     * Joins words in the data into a string.
     * @param int   $maxLength  Maximum length of result string.
     * @param array $options    (optional) Options.
     * @return string
     */
    public function toString($maxLength = 255, array $options = []) {
        $extOptions = $this->_extendDefaults($options);
        $words = $this->_getArray($this->_data, $extOptions);
        if(empty($words)) {
            return '';
        }
        $delim = isset($extOptions[self::OPTION_DELIMITER]) ? $extOptions[self::OPTION_DELIMITER] : ' ';
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
        return implode($delim, $words);
    }

    /**
     * Returns array of words with options applied.
     * @param array $words   Array of words.
     * @param array $options (optional) Options.
     * @return array
     */
    protected function _getArray(array $words, array $options = []) {
        if(empty($words)) {
            return [];
        }
        if(isset($options[self::OPTION_MAXWORDLENGTH]) && is_numeric($options[self::OPTION_MAXWORDLENGTH])) {
            $max = intval($options[self::OPTION_MAXWORDLENGTH]);
            if($max > 0) {
                foreach($words as $k => $word) {
                    if(strlen($word) > $max) {
                        $words[$k] = substr($word, 0, $max);
                    }
                }
            }
        }
        if(isset($options[self::OPTION_LOWERCASE]) && $options[self::OPTION_LOWERCASE]) {
            $words = array_map('strtolower', $words);
        }
        if(isset($options[self::OPTION_CAPITALIZE]) && $options[self::OPTION_CAPITALIZE]) {
            $words = array_map('ucfirst', $words);
        }
        return $words;
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
     * Extends (folds) option values (optionally CASE-INSENSITIVE) into associated default properties.
     * @param array $options
     * @return array
     */
    protected function _extendDefaults(array $options) {
        $defaults = array(
            self::OPTION_CAPITALIZE     => false,
            self::OPTION_DELIMITER      => ' ',
            self::OPTION_LOWERCASE      => false,
            self::OPTION_MAX            => -1,
            self::OPTION_MAXWORDLENGTH  => 30,
            self::OPTION_NODUPLICATES   => true,
            self::OPTION_SPELLCHECK     => false,
            self::OPTION_DO_NOT_FILTER  => true
        );
        return $this->_extend($defaults, $options, false, true);
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