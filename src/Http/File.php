<?php
namespace Procomputer\Pcclib\Http;
/*
 * Copyright (C) 2024 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 */
use Procomputer\Pcclib\Types;

class File {

    private $_name = '';
    private $_tmpName = '';
    private $_type = '';
    private $_error = 0;
    private $_size = 0;
    private $_fullPath = '';
    private $_errorMessage = '';
    private $_properties = [];

    private $_map = [
        'name'         => 'Name',
        'type'         => 'Type',
        'size'         => 'Size',
        'tmp_name'     => 'TmpName',
        'error'        => 'Error',
        'full_path'    => 'FullPath',
        'errorMessage' => 'ErrorMessage'
    ];
    
    /**
     *
     * @param array $properties
     */
    public function __construct(array|File $properties = null) {
        if(null === $properties) {
            return;
        }
        if(is_array($properties)) {
            foreach($properties as $name => $value) {
                $set = 'set' . $this->_map[$name];
                $this->$set($value);
            }
        }
        else {
            foreach($this->_map as $m) {
                $get = 'get' . $m;
                $set = 'set' . $m;
                $this->$set($properties->$get());
            }
        }
    }

    public function isValid() {
        foreach([$this->getName(), $this->getType(), $this->getSize(), $this->getTmpName()] as $value) {
            if(Types::isBlank($value)) {
                return false;
            }
        }
        return true;
    }
    
    public function getName() {
        return $this->_name;
    }

    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getTmpName() {
        return $this->_tmpName;
    }

    public function setTmpName($tmpName) {
        $this->_tmpName = $tmpName;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function setType($type) {
        $this->_type = $type;
        return $this;
    }

    public function getSize() {
        return $this->_size;
    }

    public function setSize($size) {
        $this->_size = $size;
        return $this;
    }

    public function getFullPath() {
        return $this->_fullPath;
    }

    public function setFullPath($fullPath) {
        $this->_fullPath = $fullPath;
        return $this;
    }

    public function getError() {
        return $this->_error;
    }

    public function setError($error) {
        $this->_error = $error;
        return $this;
    }

    public function getErrorMessage() {
        return $this->_errorMessage;
    }

    public function setErrorMessage($errorMessage) {
        $this->_errorMessage = $errorMessage;
        return $this;
    }
    
    public function getProperties() {
        return $this->_properties;
    }

    public function setProperties(array $properties) {
        $this->_properties = $properties;
        return $this;
    }

    public function getProperty($key, $default = null) {
        return $this->_properties[$key] ?? $default;
    }

    public function setProperty($key, $value) {
        $this->_properties[$key] = $value;
        return $this;
    }

}