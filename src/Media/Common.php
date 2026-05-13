<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR
A PARTICULAR PURPOSE. See the GNU General Public License
for more details.
*/
/*
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : Common class extended by Media/image classes.
*/
namespace Procomputer\Pcclib\Media;

use Procomputer\Pcclib\Media\MemoryLog;
use Procomputer\Pcclib\Media\Library\Gd\Gd;

/**
 * Common class extended by Media/image classes.
 */
class Common {

    use Memory;
    
    /**
     * The Gd graphics library class.
     * @var Gd
     */
    protected $_gd;
    
    /**
     * 
     * @var string
     */
    public $lastErrorMsg = '';
    
    /**
     * 
     * @var mixed
     */
    public $lastErrorCode = 0;

    /**
     * When this property is true memory usage is logged.
     * @var int|bool
     */
    protected $_logging = false;

    /**
     * 
     * @var array
     */
    protected $_options;
    
    /**
     * Constructor
     * @param array $options (optional) Options.
     */
    public function __construct(array $options = []) {
        $this->_options = $options;
        $this->_gd = new Gd();
    }
    
    /**
     * 
     * @return Gd
     */
    public function getGd(): Gd {
        return $this->_gd;
    }
    
    /**
     * Returns logging property.
     * @return int|bool
     */
    public function getLogging(): int|bool {
        return $this->_logging;
    }

    /**
     * Sets logging property.
     * @param int|bool $logging
     * @return $this
     */
    public function setLogging(int|bool $logging) {
        $this->_logging = $logging;
        return $this;
    }
    
    /**
     * Formats a number to represent BYTES like 12M, 12K, 12G, 12T
     * @param float|int|string $size
     * @param int              $precision
     * @return type
     */
    protected function _formatBytes(int|float|string $size, int $precision = 2) {
        $base = log((float)$size, (float)1024);
        $floor = floor($base);
        $b = (float)($base - $floor);
        $pow = pow((float)1024, $b);
        $num = round($pow, $precision);
        $suffixes = array('', 'K', 'M', 'G', 'T');
        return $num . ' '. $suffixes[(int)$floor];
    }
    
    /**
     * 
     * @param MemoryLog $memoryLog
     * @return void
     */
    protected function _logMemUse(MemoryLog $memoryLog, $arg2, $arg3 = null) {
        $size = is_object($arg3) ? (imagesx($arg3) * imagesy($arg3)) : $arg3;
        $memoryLog->log($arg2, $size);
    }
}