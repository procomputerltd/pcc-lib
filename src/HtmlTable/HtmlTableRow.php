<?php
/**
 * Builds an HTML table.
 */
namespace Procomputer\Pcclib\HtmlTable;

class HtmlTableRow extends HtmlTableCommon {
  
    /**
     * Render an HTML row element.
     *
     * @param array $options (optional) Render options.
     *
     * @return string
     */
    public function render($options = null) {
        $aOptions = (null === $options) ? [] : (array)$options;
        $aOptions['tag'] = 'tr';
        $return = parent::render($aOptions);
        return $return;
    }
    
}
