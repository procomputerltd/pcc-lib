<?php
namespace Procomputer\Pcclib\Messages;

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

class Message {
    
    private $_type = 'default';
    private $_title = 'Message';
    private $_message = 'no message available';
    
    /**
     * 
     * @param string|\Throwable|Message $message
     * @param string $type  (optional) An alert type eg 'info', 'warning', 'error', 'danger'
     * @param string $title (optional) Message title else $type used.
     */
    public function __construct(string|\Throwable|Message $message = null, string $type = 'default', string $title = '') {
        if(null !== $message) {
            $this->setMessage($message, $type, $title);
        }
    }
    
    /**
     * Sets the message.
     * @param string|\Throwable $message
     * @param string $type  (optional) An alert type eg 'info', 'warning', 'error', 'danger' 
     * @param string $title (optional) Message title else $type used.
     * @return $this 
     */
    public function setMessage(string|\Throwable|Message $message, string $type = 'default', string $title = '') {
        if($message instanceof Message) {
            $this->_message = $message->getMessage();
            $this->_type = $message->getType();
            $this->_title = $message->getTitle();
        }
        else {
            $this->_message = $message;
            $this->_type = $type;
            $this->_title = $title;
        }
        return $this;
    }

    /**
     * Return the message.
     * @return string
     */
    public function getMessage() {
        return $this->getIsException() ? $this->_message->getMessage() : $this->_message;
    }

    /**
     * Return the message type.
     * @return string
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * Return the message title.
     * @return string
     */
    public function getTitle() {
        return $this->_title;
    }
    
    /**
     * Return the message.
     * @return string
     */
    public function getException() {
        return $this->getIsException() ? $this->_message : null;
    }

    /**
     * Return the message.
     * @return string
     */
    public function getIsException() {
        return is_object($this->_message);
    }

}