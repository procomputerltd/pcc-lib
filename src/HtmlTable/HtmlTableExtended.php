<?php
namespace Procomputer\Pcclib\HtmlTable;

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
use Procomputer\Pcclib\Arrays;
use Procomputer\Pcclib\Types;

/*
 * HtmlTableExtended extends HtmlTable to allow more options when building an HTML table.
 * Options available (case insensitive): 
 *     attributes       array|string      Table element attributes. 
 *     callback         callable          A callback function
 *     escape           int|array|string  One or more 'HTMLTABLE_ESCAPE_*' constants. 
 *     escapeencoding   string            Escape encoding eg 'utf-8'
 *     escapecharflags  int               Escape character flags eg ENT_QUOTES | ENT_SUBSTITUTE
 *     headers          array             Column headers. 
 *     sort             bool              Apply sorting arrows in the headers. 
 *     sortattributes   array             Attributes applied to column sorting hyperlink.
 *     rowcolumn        bool|string       Display a number row column. May be bool or string eg '#' 
 *     title            string            Table title
 */
class HtmlTableExtended {
    
    const HTMLTABLE_ESCAPE_ALL           = 0xfff; 
    const HTMLTABLE_ESCAPE_COLUMNS       = 1;
    const HTMLTABLE_ESCAPE_HEADERS       = 2;
    const HTMLTABLE_ESCAPE_SUBTITLE      = 4;
    const HTMLTABLE_ESCAPE_TITLE         = 8;
    const HTMLTABLE_ESCAPE_ALL_TEXT      = 'all';
    const HTMLTABLE_ESCAPE_COLUMNS_TEXT  = 'columns';
    const HTMLTABLE_ESCAPE_HEADERS_TEXT  = 'headers';
    const HTMLTABLE_ESCAPE_SUBTITLE_TEXT = 'subtitle';
    const HTMLTABLE_ESCAPE_TITLE_TEXT    = 'title';

    protected $_htmlTable = null;
    
    /**
     * Returns the html table creator object.
     * @param bool $createNew Create a new html table creator object.
     * @return HtmlTable
     */
    public function getHtmlTable(bool $createNew = false) {
        if($createNew || null === $this->_htmlTable) {
            $this->_htmlTable = new HtmlTable();
        }
        return $this->_htmlTable;
    }
    
    /**
     * 
     * @param iterable $data
     * @param array    $options
     * @return string
     */
    public function render(iterable $data, array $options = []): string {
        $columnCount = 0;
        $dataObj = new \ArrayObject();
        foreach($data as $dataRow) {
            $array = (array)$dataRow;
            $c = count($array);
            if($columnCount < $c) {
                $columnCount = $colspan = $c;
                $rowKeys = array_keys($array);
            }
            $dataObj->append($array);
        }
        if(! $columnCount) {
            return '';
        }
        $defaults = [
            'attributes'      => false,
            'callback'        => false,
            'escape'          => false,
            'escapeencoding'  => 'utf-8',
            'escapecharflags' => ENT_QUOTES | ENT_SUBSTITUTE,
            'headers'         => false,
            'rowcolumn'       => false,
            'sort'            => false,
            'title'           => false,
        ];
        $lcOptions = Arrays::extend($defaults, $options);
        if($lcOptions['rowcolumn']) {
            $colspan++;
            $rowColumn = is_string($lcOptions['rowcolumn']) ? $this->_toString($lcOptions['rowcolumn'], 64) : '#';
        }
        else {
            $rowColumn = false;
        }
        /** @var HtmlTableEscape $htmlEscape */
        $htmlEscape = $lcOptions['escape'] ? new HtmlTableEscape($lcOptions['escape'], $lcOptions['escapecharflags'], $lcOptions['escapeencoding']) : false;
        $htmlTable = $this->getHtmlTable();
        if($lcOptions['title']) {
            $content = $lcOptions['title'];
            if(! is_array($content)) {
                $content = [$this->_toString($content)];
            }
            if($htmlEscape) {
                $content = $htmlEscape->escape($content, 'title');
            }
            $htmlTable->add($content, ['class' => 'title', 'colspan' => $colspan]);
        }
        $headers = $lcOptions['headers'];
        if(! is_array($headers) || ! count($headers)) {
            $headers = $headers ? $rowKeys : false;
        }
        if($headers) {
            if($htmlEscape) {
                $headers = $htmlEscape->escape($headers, self::HTMLTABLE_ESCAPE_HEADERS);
            }
            if(is_array($lcOptions['sort'])) {
                $htmlSort = new HtmlTableSort($lcOptions['sort']);
                $headers = $htmlSort->createLinks($headers);
            }
            if($rowColumn) {
                array_unshift($headers, $rowColumn);
            }
            $htmlTable->add($headers, ['class' => 'header']);
        }
        $rowCount = 0;
        foreach($dataObj as $dataRow) {
            if($htmlEscape) {
                $dataRow = $htmlEscape->escape($dataRow, 'columns');
            }
            if($rowColumn) {
                $dataRow = array_merge([++$rowCount], $dataRow);
            }
            $htmlTable->add($dataRow, ['class' => 'column']);
        }   
        $attr = $lcOptions['attributes'];
        if(is_array($attr)) {
            $attributes = $attr;
        }
        elseif(is_string($attr)) {
            $attr = trim($attr);
            if(strlen($attr)) {
                $attributes = ['class' => $attr];
            }
        }
        return $htmlTable->render(empty($attributes) ? [] : ['attributes' => $attributes]);
    }

    /**
     * Decodes a sort value into a 3-element array of constituent parts as follows: [column, current, descending]
     * @return array
     */
    public function decodeSortValue(mixed $sortValue): array {
        $obj = new HtmlTableSort();
        return $obj->decodeSortValue($sortValue);
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
