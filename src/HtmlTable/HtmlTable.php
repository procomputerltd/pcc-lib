<?php
/**
 * Builds an HTML table.
 */
namespace Procomputer\Pcclib\HtmlTable;

class HtmlTable extends HtmlTableCommon {

    /**
     * Render an HTML table.
     *
     * @param array $options (optional) Render options. The HTML table is rendered
     *                                  from 'rows' option if it exists else from
     *                                  rows and columns added using 'add()' method.
     *
     * @return string Returns the rendered HTML table.
     */
    public function render(array $options = []) {
        $lcOptions = array_change_key_case($options);
        if(isset($lcOptions['rows'])) {
            return $this->_render($lcOptions);
        }
        $options['tag'] = 'table';
        $return = parent::render($options);
        return $return;
    }

    /**
     * Render an HTML table from an array or Traversable specified in 'rows'
     * option with optional header specified in 'header' option.
     *
     * @param array $options (optional) Render options. NOTE: the HTML table is rendered from 'rows' option.
     *
     * @return string Returns the rendered HTML table.
     */
    protected function _render(array $options = []) {
        $rows = $this->_toArray($options['rows']);
        $colCount = 0;
        foreach($rows as $label => $row) {
            if(! is_array($row)) {
                $row = $rows[$label] = $this->_toArray($row);
            }
            $c = count($row) ;
            if($colCount < $c) {
               $colCount = $c;
            }
        }

        if(isset($options['header'])) {
            $header = $this->_toArray($options['header']);
            if(count($header) > $colCount) {
                $header = array_slice($header, 0, $colCount);
            }
            else {
                $header = array_pad($header, $colCount, '-');
            }
            $headerRow = $this->add();
            $attr = isset($options['headerattributes']) ? $this->_toArray($options['headerattributes']) : [];
            foreach($header as $label) {
                $headerRow->add($label, $attr);
            }
        }

        $noEscape = $options['noescape'] ?? false;
        $attr = isset($options['attributes']) ? $this->_toArray($options['attributes']) : [];
        foreach($rows as $key => $values) {
            $row = $this->add();
            foreach($values as $value) {
                $row->add($noEscape ? $value : $this->_escape($value), $attr);
            }
        }
        unset($options['rows'], $options['header'], $options['noescape']);
        $options['tag'] = 'table';
        $return = parent::render($options);
        return $return;
    }
}
