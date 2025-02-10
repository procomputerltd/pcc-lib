<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR
A PARTICULAR PURPOSE. See the GNU General Public License
for more details.
*/
namespace Procomputer\Pcclib\HtmlTable;

use Procomputer\Pcclib\Html\Common as HtmlCommon;

class HtmlTableCommon extends HtmlCommon {

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
     * @param array  $attributes (optional)
     * @param string $innerHtml  (optional)
     */
    public function __construct(array $attributes = [], string $innerHtml = '') {
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
        if($this instanceof HtmlTableCol) {
            throw new \RuntimeException("error: an attempt is made to add children to HtmlTableCol column object. HtmlTableCol column objects have no children.");
        }
        if($values && $this instanceof HtmlTable) {
            $row = $this->_children[] = new HtmlTableRow();
            return $row->add($values, $attributes);
        }
        $isTableRow = ($this instanceof HtmlTableRow /* else HtmlTableCol, HtmlTable */);
        if(null === $values) {
            $obj = $this->_children[] = $isTableRow ? new HtmlTableCol($attributes, '') : new HtmlTableRow($attributes, '');
        }
        else {
            foreach($this->_toArray($values) as $value) {
                $obj = $this->_children[] = $isTableRow ? new HtmlTableCol($attributes, $value) : new HtmlTableRow($attributes, $value);
            }
        }
        return $obj ?? null;
    }

    /**
     * Sets an HTML element attribute.
     * @param string $name  Attribute name.
     * @param string $value Attribute value.
     * @return $this
     */
    public function setAttribute(string $name, string $value) {
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
        $lcOptions = array_change_key_case($options);
        if(isset($lcOptions['tag'])) {
            $attributes = $this->_attributes;
            if($this instanceof HtmlTable && isset($lcOptions['attributes']) && is_array($lcOptions['attributes'])) {
                $attributes = array_merge($attributes, $lcOptions['attributes']);
            }
            $html = "<{$lcOptions['tag']}{$this->buildAttribs($attributes)}>\n" . $html . "</{$lcOptions['tag']}>";
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
     * Converts special characters to HTML entities
     * @param string|array|Traversable $value
     * @return string|array
     */
    protected function _escape(mixed $value) {
        $isArray = (is_array($value) || $value instanceof \Traversable);
        $escaped = array_map('htmlspecialchars', $this->_toArray($value));
        return $isArray ? $escaped : reset($escaped);
    }

    /**
     * Converts undetermined value to an array.
     * @param string|array $mixed
     * @return array
     */
    protected function _toArray(mixed $mixed) {
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
