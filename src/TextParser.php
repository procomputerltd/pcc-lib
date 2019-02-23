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
    Description : Text parsing collection class.
*/
namespace Procomputer\Pcclib;

/**
 * Text parsing collection class.
 */
class TextParser implements \Iterator {

    /**
     * The regular expression pattern or patterns to parse from the text string.
     * @var array|string
     */
    protected $_regexPattern = null;

    /**
     * The index of the regex pattern that matched this string part.
     * @var int
     */
    protected $_patternIndex = null;

    /**
     * The item type or properties
     * @var string
     */
    protected $_type = '';

    /**
     * The item description
     * @var string
     */
    protected $_description = '';

    /**
     * The item text value.
     * @var string
     */
    protected $_text = '';

    /**
     * Matching items array returned by preg_split.
     * @var array
     */
    protected $_matches = array();

    /**
     * Sub-item iterator index.
     * @var array
     */
    protected $_index = 0;

    /**
     * Constructor set the regular espression pattern(s)
     *
     * @param string $regexPattern (optional) Regular expression pattern or patterns to parse from the text string.
     */
    public function __construct($regexPattern = null) {
        if(null !== $regexPattern) {
            $this->setRegexPattern($regexPattern);
        }
    }

    /**
     *
     * @param string $text         Text to parse.
     * @param string $regexPattern (optional) Regular expression pattern or patterns to parse from the text string.
     * @return boolean|null
     */
    public function parse($text, $regexPattern = null) {
        if(null === $regexPattern) {
            $regexPattern = $this->getRegexPattern();
        }

        if(! is_string($text)) {
            $msg = "Invalid text parameter: expecting string";
            throw new Exception\InvalidArgumentException($msg);
        }
        if(! strlen($text)) {
            // No text, no matches
            return null;
        }

        if(is_array($regexPattern)) {
            if(! count($regexPattern)) {
                // Error: no patterns.
                return false;
            }
            // Ensure the patterns a indexed by integers.
            $patterns = array_values($regexPattern);
        }
        elseif(is_string($regexPattern) && strlen(trim($regexPattern))) {
            $patterns = array(0 => $regexPattern);
        }
        else {
            // Error: invalid paramter.
            return false;
        }

        $matchIndex = $this->_getMatchIndex($patterns, $text);
        if(false === $matchIndex) {
            // PCRE error?
            return false;
        }
        if(null === $matchIndex) {
            // No match.
            return null;
        }

        $this->_patternIndex = $matchIndex;

        $matches = preg_split($patterns[$matchIndex], $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Remove the pattern from the pattern list for subequent substring searches..
        $patterns[$matchIndex] = null;

        foreach($patterns as $pattern) {
            if(is_string($pattern) && strlen($pattern)) {
                for($index = 0; $index < count($matches); $index += 2) {
                    if(! is_string($matches[$index]) || strlen(trim($matches[$index]))) {
                        $class = get_class($this);
                        $item = new $class();
                        $res = $item->parse($matches[$index], $patterns);
                        if(false === $res) {
                            return false;
                        }
                        if(null !== $res) {
                            $matches[$index] = $item;
                        }
                    }
                }
                break;
            }
        }
        $this->setMatches($matches);

        return true;
    }

    protected function _getMatchIndex(array $patterns, $subject, $flags = null) {
        foreach($patterns as $key => $pattern) {
            if(is_string($pattern) && strlen($pattern)) {
                $match = preg_match($pattern, $subject);
                if(false === $match) {
                    return false;
                }
                if($match) {
                    return $key;
                }
            }
        }
        return null;
    }

    /**
     * Assemble the text string from the pattern items.
     * @return string
     */
    public function assemble() {
        if(! $this->count()) {
            return '';
        }
        $subStrings = array();
        foreach($this->_matches as $key => $item) {
            /* @var $item TextParser */
            if(is_object($item)) {
                $text = $item->assemble();
            }
            else {
                $text = $item;
            }
            $subStrings[] = $text;
        }
        $return = implode('', $subStrings);
        return $return;
    }

    /**
     * Returns matching items array returned by preg_split.
     *
     * @return array
     *
     */
    public function getMatches() {
        return $this->_matches ;
    }

    /**
     * Sets matching items array returned by preg_split.
     *
     * @param array $matches
     *
     * @return TextParser
     */
    public function setMatches($matches) {
        $this->_matches = $matches ;
        return $this ;
    }

    /**
     * Returns the regex pattern with which to parse text.
     *
     * @return array|string
     *
     */
    public function getRegexPattern() {
        return $this->_regexPattern ;
    }

    /**
     * Sets the regex pattern with which to parse text.
     *
     * @param array|string $regexPattern
     *
     * @return TextParser
     */
    public function setRegexPattern($regexPattern) {
        $this->_regexPattern = $regexPattern ;
        return $this ;
    }

    /**
     * Returns the index of the regex pattern that matched this string part.
     *
     * @return int
     *
     */
    public function getPatternIndex() {
        return $this->_patternIndex ;
    }

    /**
     * Sets the index of the regex pattern that matched this string part.
     *
     * @param int $patternIndex
     *
     * @return TextParser
     */
    public function setPatternIndex($patternIndex) {
        $this->_patternIndex = $patternIndex ;
        return $this ;
    }

    /**
     * Returns the item type or properties
     *
     * @return string
     *
     */
    public function getType() {
        return $this->_type ;
    }

    /**
     * Sets the item type or properties
     *
     * @param string $value
     *
     * @return TextParser
     */
    public function setType($value) {
        $this->_type = $value ;
        return $this ;
    }

    /**
     * Returns the item description
     *
     * @return string
     *
     */
    public function getDescription() {
        return $this->_description ;
    }

    /**
     * Sets the item description
     *
     * @param string $description
     *
     * @return TextParser
     */
    public function setDescription($description) {
        $this->_description = $description ;
        return $this ;
    }

    /**
     * Returns item text value.
     *
     * @return string
     *
     */
    public function getText() {
        return $this->_text ;
    }

    /**
     * Sets item text value.
     *
     * @param string $text
     *
     * @return TextParser
     */
    public function setText($text) {
        $this->_text = $text ;
        return $this ;
    }

    /**
     * Returns the number of items.
     * @return int
     */
    public function count() {
        return count($this->_matches);
    }

    /**
     * Methods implements from Iterator interface (Core.php)
     * @return int
     */
    public function rewind() {
        $this->_index = 0;
    }
    /**
     * @return mixed
     */
    public function current() {
        return $this->_matches[$this->_index];
    }
    /**
     * @return int
     */
    public function key() {
        return $this->_index;
    }
    /**
     * @return int
     */
    public function next() {
        ++$this->_index;
    }
    /**
     * @return boolean
     */
    public function valid() {
        return isset($this->_matches[$this->_index]);
    }
}
