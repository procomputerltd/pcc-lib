<?php
/**
 * Builds an HTML table.
 */
namespace Procomputer\Pcclib\HtmlTable;

class HtmlTableCol extends HtmlTableCommon {

    /**
     * Render an HTML column element.
     *
     * @param array $options (optional) Render options.
     *
     * @return string
     */
    public function render(array $options = []) {
        $return = "<td{$this->buildAttribs($this->_attributes)}>{$this->_innerHtml}</td>";
        return $return;
    }
}
