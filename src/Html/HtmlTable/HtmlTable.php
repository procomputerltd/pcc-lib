<?php
/**
 * Builds an HTML table.
 */
namespace Procomputer\Pcclib\Html\HtmlTable;

class HtmlTable extends HtmlTableCommon {
    
    /**
     * Render an HTML table.
     *
     * @param array $options (optional) Render options.
     *
     * @return string
     */
    public function render($options = null) {
        $aOptions = (null === $options) ? [] : (array)$options;
        $aOptions['tag'] = 'table';
        $return = parent::render($aOptions);
        return $return;
    }
}
