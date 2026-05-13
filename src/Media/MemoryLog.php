<?php
namespace Procomputer\Pcclib\Media;

/*
 * Copyright (c) Pro Computer Consultants
 * All rights reserved
 */
use Procomputer\Pcclib\HtmlTable\HtmlTable;
class MemoryLog {

    const SORT_NONE = 0;
    const SORT_SIZE = 1;
    const SORT_NAME = 2;
    const SORT_MEM = 3;
    
    public static $log = [];

    public static $name = '';

    protected static $_memLimit = null;

    protected $_properties = [
        'Name' => '',
        'Context' => '',
        'Used' => 0,
        'Size' => 0,
        'Ratio' => 0,
        'Start' => 0,
        'End' => 0
    ];

    public function __construct() {
        $this->_properties['Start'] = memory_get_usage();
    }

    public function log(string $context, int|float $size, ?string $name = null) {
        $this->_properties['End'] = memory_get_usage();
        $this->_properties['Used'] = $this->_properties['End'] - $this->_properties['Start'];
        $this->_properties['Name'] = $name ? $name : self::$name;
        $this->_properties['Context'] = $context;
        $this->_properties['Size'] = $size;
        $this->_properties['Ratio'] = $size ? ((float)$this->_properties['Used'] / (float)$size) : 0;
        self::$log[] = $this->_properties;
    }

    public static function getLog() {
        return self::$log;
    }

    public static function buildLogTable(int $sort = self::SORT_NONE) {
        $log = self::getLog();
        if(! count($log)) {
            return '';
        }
        switch($sort) {
        case self::SORT_SIZE:
        case self::SORT_NAME:
        case self::SORT_MEM:
            break;
        default:
            $sort = self::SORT_NONE;
        }
        $numCols = 0;
        $array = [];
        foreach($log as $properties) {
            $name = $properties['Name'];
            unset($properties['Name']);
            $array[$name][] = $properties;
            $c = count($properties);
            if($numCols < $c) {
                $columnHeaders = array_keys($properties);
                $i = array_search('Context', $columnHeaders);
                if(false !== $i) {
                    $columnHeaders[$i] = '';
                }
                $numCols = $c;
            }
        }
        if(self::SORT_NONE !== $sort) {
            $sortArray = [];
            foreach($array as $key => $propList) {
                $properties = reset($propList);
                switch($sort) {
                case self::SORT_SIZE:
                    $value = $properties['Size'];
                    break;
                case self::SORT_NAME:
                    $value = $properties['Name'];
                    break;
                case self::SORT_MEM:
                    $value = $properties['Used'];
                    break;
                }
                $sortArray[$key] = $value;
            }
            /**
            SORT_REGULAR - compare items normally; the details are described in the comparison operators section
            SORT_NUMERIC - compare items numerically
            SORT_STRING - compare items as strings
            SORT_LOCALE_STRING - compare items as strings, based on the current locale. It uses the locale, which can be changed using setlocale()
            SORT_NATURAL - compare items as strings using "natural ordering" like natsort()
            SORT_FLAG_CASE            
             */
            switch($sort) {
            case self::SORT_SIZE:
            case self::SORT_MEM:
                arsort($sortArray, SORT_NUMERIC);
                break;
            default:
                asort($sortArray, SORT_REGULAR | SORT_FLAG_CASE);
            }
            $temp = [];
            foreach($sortArray as $key => $properties) {
                $temp[$key] = $array[$key];
            }
            $array = $temp;
            unset($temp);
        }
        $table = new HtmlTable();
        $table->add(['Memory limit: ' . number_format(self::getMemLimit())], ['colspan' => $numCols, 'style' => 'font-size:120%;font-weight:bold']);
        $table->add($columnHeaders);
        foreach($array as $name => $propList) {
            $table->add([$name], ['colspan' => $numCols, 'style' => 'font-weight:bold']);
            foreach($propList as $properties) {
                $properties['Size'] = number_format($properties['Size']);
                $properties['Used'] = number_format($properties['Used']);
                $properties['Ratio'] = number_format($properties['Ratio'], 2);
                $properties['Start'] = number_format($properties['Start']);
                $properties['End'] = number_format($properties['End']);
                $table->add($properties);
            }
        }
        return $table->render();
    }

    /**
     * Returns available script memory.
     * @return int
     */
    public static function getMemLimit(): int {
        if(self::$_memLimit === null) {
            $limit = strtolower(strval(ini_get('memory_limit')));
            self::$_memLimit = preg_match('/^(\\d+)m(.*)$/', $limit, $m) ? (intval($m[1]) * 1024 * 1024) : -1;
        }
        return self::$_memLimit;
    }
}