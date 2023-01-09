<?php
/**
 */
namespace Procomputer\Pcclib\HtmlTable;

class HtmlTableCommon {

    /**
     * The flag that indicates values shall be escaped using htmlentities()
     */

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
     * @return HtmlTableCommon Return the row or column object.
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
    public function render(array $options = []) {
        $children = [];
        foreach($this->_children as $obj) {
            $children[] = $obj->render($options);
        }
        $html = implode("\n    ", $children);
        if(isset($options['tag'])) {
            $attr = $this->_buildAttribs($options['attributes'] ?? $this->_attributes);
            $html = "<{$options['tag']}{$attr}>\n" . $html . "</{$options['tag']}>";
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
     * @return string|array
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
    
    /**
     * Converts undetermined value to an array.
     * @param string|array $mixed
     * @return array
     */
    protected function _toArray($mixed) {
        if(is_array($mixed)) {
            return $mixed;
        }
        if(is_object($mixed)) {
            if(method_exists($mixed, 'getArrayCopy')) {
                return $mixed->getArrayCopy();
            }
            if($mixed instanceof \Traversable) {
                $return = [];
                foreach($mixed as $k => $v) {
                    $return[$k] = $v;
                }
                return $return;
            }
        }
        return is_scalar($mixed) ? ([is_bool($mixed) ? ($mixed ? 'true' : 'false') : (string)$mixed]) : [gettype($mixed)];
    }
}
