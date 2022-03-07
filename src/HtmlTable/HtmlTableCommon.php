<?php
/**
 */
namespace Procomputer\Pcclib\HtmlTable;

use Procomputer\HtmlTable\HtmlTableRow;
use Procomputer\HtmlTable\HtmlTableCol;

class HtmlTableCommon {

    /**
     * The flag that indicates values shall be escaped using htmlentities()
     */
    const HTMLTABLE_ESCAPE = 0x100;

    /**
     * The flag that indicates headers, labels and titles shall be escaped using htmlentities()
     */
    const HTMLTABLE_ESCAPE_HEADER = 0x200;

    /**
     * The flag that indicates array keys shall be used as column headers.
     */
    const HTMLTABLE_USE_KEYS_AS_COLUMN_HEADERS = 0x400;

    /**
     * The flag that indicates array keys shall be used as column headers.
     */
    const HTMLTABLE_USE_KEYS_AS_ROW_LABLES = 0x800;

    /**
     * The default element name of checkbox elements.
     * @var string
     */
    const HTMLTABLE_CHECKBOX_MULTIPLE = 'chkMultiSelect';

    /**
     * The tag that indicates an array element is a row header.
     * @var string
     */
    const HTMLTABLE_ROW_HEADER = '__row_header__';

    /**
     * The tag that indicates an array element is a row separator.
     * @var string
     */
    const HTMLTABLE_ROW_SEPARATOR = '__row_separator__';

    /**
     * Element value.
     * @var string
     */
    protected $_innerHtml = '';

    /**
     * Element attributes.
     * @var array 
     */
    protected $_attributes = [];

    /**
     * Element children elements.
     * @var array 
     */
    protected $_children = [];

    /**
     * Ctor
     * @param array $attributes (optional)
     * @param mixed $innerHtml  (optional)
     */
    public function __construct(array $attributes = [], $innerHtml = '') {
        $this->_attributes = $attributes;
        $this->_innerHtml = $innerHtml;
    }
    
    /**
     * Add a table row or column(s)
     * @param mixed $values     (optional)
     * @param array $attributes (optional)
     * 
     * @return HtmlRow|HtmlCol Return the row or column object.
     */
    public function add($values = null, array $attributes = []) {
        $isTableRow = ($this instanceof HtmlTableRow);
        if(null === $values) {
            $obj = $this->_children[] = $isTableRow ? new HtmlTableCol($attributes, '') : new HtmlTableRow($attributes, '');
        }
        else {
            foreach((array)$values as $value) {
                $obj = $this->_children[] = $isTableRow ? new HtmlTableCol($attributes, $value) : new HtmlTableRow($attributes, $value);
            }
        }
        return $obj;
    }
    
    /**
     * Sets an HTML element attribute.
     * @param string $name
     * @param mixed  $value
     * @return $this
     */
    public function setAttribute($name, $value) {
        $this->_attributes[$name] = $value;
        return $this;
    }
    
    /**
     * Render an HTML element.
     *
     * @param array $options (optional) Render options.
     *
     * @return string
     */
    public function render($options = null) {
        $aOptions = (null === $options) ? [] : (array)$options;
        $children = [];
        foreach($this->_children as $obj) {
            $children[] = $obj->render($options);
        }
        $html = implode("\n    ", $children);
        if(isset($aOptions['tag'])) {
            $attr = $this->_buildAttribs($aOptions['attributes'] ?? $this->_attributes);
            $html = "<{$aOptions['tag']}{$attr}>\n" . $html . "</{$aOptions['tag']}>";
        }
        return $html;
    }
    
    /**
     * Clears elements, values.
     * @return $this
     */
    public function clear() {
        $this->_innerHtml = '';
        $this->_children = [];
        return $this;
    }
    
    /**
     * Builds HTML element attribute declarations.
     * @param array $attr
     * @return string
     */
    protected function _buildAttribs(array $attr) {
        $return = array();
        foreach($attr as $name => $value) {
            if(is_string($name) && !empty($value)) {
                $return[] = $name . '="' . str_replace('"', '&quot;', $value) . '"';
            }
        }

        return empty($return) ? '' : (' ' . implode(' ', $return));
    }
    
    /**
     * Converts special characters to HTML entities
     * @param string|array $value
     */
    protected function _escape($value) {
        if(is_array($value)) {
            return array_map('htmlspecialchars', $value);        
        }
        if(is_string($value)) {
            return htmlspecialchars($value);
        }
        return $value;
    }
}
