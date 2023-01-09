<?php
/**
 * Builds an HTML table.
 */
namespace Procomputer\Pcclib\HtmlTable;

class HtmlTable extends HtmlTableCommon {
    
    /**
     * Render an HTML table.
     *
     * @param array $options (optional) Render options.
     *
     * @return string
     */
    public function render(array $options = []) {
        if(isset($options['rows'])) {
            return $this->_render($options);
        }
        $options['tag'] = 'table';
        $return = parent::render($options);
        return $return;
    }
    
    /**
     * Render an HTML table.
     *
     * @param array $options (optional) Render options.
     *
     * @return string
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
            foreach($header as $label) {
                $headerRow->add($label, ['class' => 'header']);
            }
        }

        foreach($rows as $key => $values) {
            $row = $this->add();
            foreach($values as $value) {
                $row->add(htmlspecialchars($value));
            }
        }
        unset($options['rows'], $options['header']);
        $options['tag'] = 'table';
        $return = parent::render($options);
        return $return;
    }
}
