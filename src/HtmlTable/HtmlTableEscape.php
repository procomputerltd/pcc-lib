<?php
/* 
 * Copyright (C) 2024 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
namespace Procomputer\Pcclib\HtmlTable;

use Procomputer\Pcclib\Types;

class HtmlTableEscape {

    protected $_escape = false;
    protected $_flags = 0;
    protected $_encoding = null;
    
    public function __construct(mixed $options = [],  int $htmlSpecialCharsFlags = ENT_QUOTES | ENT_SUBSTITUTE, ?string $encoding = null) {
        $this->_escape = $this->resolveEscape($options);
        $this->_flags = $htmlSpecialCharsFlags;
        $this->_encoding = $encoding;
    }

    /**
     * 
     * @param array $data
     * @param mixed $escape
     * @return array
     */
    public function escape(mixed $data, mixed $escape) {
        $bitMask = $this->resolveEscape($escape);
        if(! ($bitMask & $this->_escape)) {
            return $data;
        }
        $isArray = is_array($data);
        if(! $isArray) {
            $data = [$this->_toString($data)];
        }
        array_walk($data, function(&$value) {
            $value = htmlspecialchars(is_string($value) ? $value : $this->_toString($value), $this->_flags, $this->_encoding);
        });
        return $isArray ? $data : reset($data);
    }
    
    /**
     * Resolve an escape specifier.
     * @param mixed $escape May be one or more 'HTMLTABLE_ESCAPE_*' constants OR'd 
     * @return int
     */
    public function resolveEscape(mixed $escape): int {
        if(! $escape) {
            return 0;
        }
        if(is_bool($escape)) {
            return $escape ? HtmlTableExtended::HTMLTABLE_ESCAPE_ALL : 0;
        }
        if(is_numeric($escape)) {
            return intval($escape);
        }
        if(! is_array($escape) && ! is_string($escape)) {
            return 0;
        }
        if(! is_array($escape)) {
            $escape = [$escape];
        }
        $tags = array_map('strtolower', array_map('trim', $escape));
        $map = [
           HtmlTableExtended::HTMLTABLE_ESCAPE_ALL_TEXT      => HtmlTableExtended::HTMLTABLE_ESCAPE_ALL, 
           HtmlTableExtended::HTMLTABLE_ESCAPE_COLUMNS_TEXT  => HtmlTableExtended::HTMLTABLE_ESCAPE_COLUMNS,
           HtmlTableExtended::HTMLTABLE_ESCAPE_HEADERS_TEXT  => HtmlTableExtended::HTMLTABLE_ESCAPE_HEADERS,
           HtmlTableExtended::HTMLTABLE_ESCAPE_SUBTITLE_TEXT => HtmlTableExtended::HTMLTABLE_ESCAPE_SUBTITLE,
           HtmlTableExtended::HTMLTABLE_ESCAPE_TITLE_TEXT    => HtmlTableExtended::HTMLTABLE_ESCAPE_TITLE
        ];
        $return = 0;
        foreach($tags as $tag) {
            if(is_numeric($tag)) {
                $return |= (int)$tag;
            }
            elseif(isset($map[$tag])) {
               $return |= $map[$tag];
            }
        }
        return $return;
    }
    
    /**
     * 
     * @param mixed $data
     * @param int $max
     * @return type
     */
    private function _toString(mixed $data, int $max = -1) {
        if(is_scalar($data)) {
            if(! is_string($data)) {
                return is_bool($data) ? ($data ? 'true' : 'false') : strval($data);
            }
            $str = $data;
        }
        else {
            $str = Types::getVartype($data);
        }
        return ($max > 0 && strlen($str) > $max) ? substr($str, 0, $max) : $str;
    }
}