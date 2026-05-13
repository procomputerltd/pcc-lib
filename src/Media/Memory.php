<?php
namespace Procomputer\Pcclib\Media;

/* 
 * Copyright (c) Pro Computer Consultants
 * All rights reserved
 */
trait Memory {
    
    protected $_memLimit = null;
    
    /**
     * 
     * @param int|float $bytes
     * @param int       $channels
     * @return bool
     */
    public function getMemCheck(int|float $bytes, int $channels = 3, float $ratio = 1.7) {
        $limitBytes = $this->getMemLimit();
        if(-1 === $limitBytes) {
            return true; // No limit
        }
        $usageGuess = $bytes * $channels * $ratio + memory_get_usage();
        return $usageGuess < $limitBytes;
    }
    
    /**
     * 
     * @param string  $functionName
     * @param int|bool $size
     * @param bool     $isFile
     * @return string
     */
    public function getMemMessage(string $functionName, int|float $size, bool $isFile = false): string {
        $avail = $this->getMemAvailable(true); // true means use number_format()
        $limit = $this->formatBytes($this->getMemLimit());
        $size = $this->formatBytes($size);
        $source = $isFile ? " from file having size {$size}" : " having {$size} pixels";
        return "insufficient memory ({$avail} bytes) to create image{$source} using" .
            " '{$functionName}' Consider increasing the PHP memory limit currently {$limit} bytes.";
    }
    
    /**
     * Returns available script memory.
     * @return int
     */
    public function getMemLimit(): int {
        if($this->_memLimit === null) {
            $limit = strtolower(strval(ini_get('memory_limit')));
            $this->_memLimit = preg_match('/^(\\d+)m(.*)$/', $limit, $m) ? (intval($m[1]) * 1024 * 1024) : -1;
        }
        return $this->_memLimit;
    }
    
    /**
     * Returns used script memory.
     * @return int
     */
    public function getMemUsed(bool $format = false): int|string {
        $val = memory_get_usage();
        return $format ? $this->formatBytes($val) : $val;
    }
    
    public function getMemAvailable(bool $format = false): int|string {
        $val = $this->getMemLimit() - $this->getMemUsed();
        return $format ? $this->formatBytes($val) : $val;
    }
    
    /**
     * Formats a number to represent BYTES like 12 M, 12 K, 12 G, 12 T
     * @param float|int|string $size
     * @param int              $precision
     * @return string
     */
    protected function formatBytes(int|float|string $size, int $precision = 0): string {
        $base = log((float)$size, (float)1024);
        $floor = floor($base);
        $b = (float)($base - $floor);
        $pow = pow((float)1024, $b);
        $num = round($pow, $precision);
        $suffixes = array('', 'K', 'M', 'G', 'T');
        return $num . ' '. $suffixes[(int)$floor];
    }
}