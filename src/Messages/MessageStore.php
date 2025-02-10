<?php
namespace Procomputer\Pcclib\Messages;

use Procomputer\Pcclib\Types;

class MessageStore {

    /**
     * Messages and errors saved.
     * @var array
     */
    private $_messages = [];

    /**
     * Saves message or messages.
     * @param string|array|\Traversable|Message|MessageStore|\Throwable $messages Message or messages.
     * @param string $messageType (optional) An alert type eg 'info', 'warning', 'error', 'danger'
     * @param string $title       (optional) Message title else $messageType used.
     * @return self
     */
    public function addMessage(string|array|\Traversable|Message|MessageStore|\Throwable $messages, string $messageType = 'default', string $title = '') {
        $defaultType = $this->_resolveMessageType($messageType);
        if(is_scalar($messages)) {
            $msg = is_bool($messages) ? Types::getVartype($messages, 0x7fff) : (string)$messages;
            $this->_messages[] = new Message($msg, $defaultType, $title);
            return $this;
        }
        
        if($messages instanceof Message) {
            $this->_messages[] = new Message($messages->getMessage(), $messages->getType(), $messages->getTitle());
            return $this;
        }
        elseif($messages instanceof MessageStore) {
            foreach($messages->getMessages() as $msgObj) {
                $this->addMessage($msgObj);
            }
            return $this;
        }
        elseif($messages instanceof \Throwable) {
            $this->_messages[] = new Message($messages, $defaultType, $title);
            return $this;
        }
        
        if(is_iterable($messages)) {
            foreach($messages as $msgType => $message) {
                if($message instanceof Message) {
                    $this->_messages[] = new Message($message);
                }
                else {
                    $msgType = is_int($msgType) ? $defaultType : $this->_resolveMessageType($msgType);
                    if(is_iterable($message)) {
                        foreach($message as $msgTitle => $msgs) {
                            if(! is_string($msgTitle)) {
                                $msgTitle = $title;
                            }
                            foreach((array)$msgs as $msg) {
                                $msg = (is_scalar($msg) && ! is_bool($msg)) ? (string)$msg : Types::getVartype($msg, 0x7fff);
                                $this->_messages[] = new Message($msg, $msgType, $msgTitle);
                            }
                        }
                    }
                    else {
                        $msg = (is_scalar($message) && ! is_bool($message)) ? (string)$message : Types::getVartype($message, 0x7fff);
                        $this->_messages[] = new Message($msg, $msgType, $title);
                    }
                }
            }
            return $this;
        }
        $msg = (is_scalar($messages) && ! is_bool($messages)) ? (string)$messages : Types::getVartype($messages, 0x7fff);
        $this->_messages[] = new Message($msg, $msgType, $title);
        return $this;
    }
    /**
     * Alias of addMessage
     * @param string|array|\Traversable|Message|MessageStore|\Throwable $messages Message or messages.
     * @param string $messageType (optional) An alert type eg 'info', 'warning', 'error', 'danger'
     * @param string $title       (optional) Message title else $messageType used.
     * @return self
     */
    public function saveMessage(string|array|\Traversable|Message|MessageStore|\Throwable $messages, string $messageType = 'default', string $title = '') {
        return $this->addMessage($messages, $messageType, $title);
    }
    
    /**
     * Returns saved messages.
     * @param string $type (optional) Type of messages to return eg 'error', 'warning'
     * @return array
     */
    public function getMessages(string $type = 'all') : array {
        /** @var Message $message */
        $msgType = strtolower(trim($type));
        if(empty($msgType) || 'all' === $msgType) {
            return $this->_messages;
        }
        $return = [];
        foreach($this->_messages as $message) {
            if($msgType === $message->getType()) {
                $return[] = $message;
            }
        }
        return $return;
    }

    /**
     * Returns number of messages.
     * @param string $type (optional) Type of messages to return eg 'error', 'warning'
     * @return array
     */
    public function getMessageCount(string $type = 'all') : int {
        return count($this->getMessages($type));
    }

    /**
     * Clears messages.
     * @return ServiceCommon
     */
    public function clearMessages() {
        $this->_messages = [];
        return $this;
    }
    
    /**
     * Merges messages to this object's messages and return a new MessageStore objject
     * @param string|array|\Traversable|Message|MessageStore|\Throwable $messages
     * @return MessageStore
     */
    public function merge(string|array|\Traversable|Message|MessageStore|\Throwable $messages) {
        if($messages instanceof MessageStore) {
            $messageStore = $messages;
        }
        else {
            $messageStore = new MessageStore();
            $messageStore->addMessage($messages);
        }
        /** @var Message $message */
        foreach($this->_messages as $message) {
            $messageStore->addMessage($message);
        }
        return $messageStore;
    }
    
    /**
     * Converts message to an array index by message type eg 'error', 'warning'
     * @param string $type    (optional) Message type.
     * @param array  $options (optional) Options.
     */
    public function toArray(string $type = 'all', array $options = []) {
        /** @var Message $message */
        $return = [];
        foreach($this->getMessages($type) as $message) {
            $return[$message->getType()][$message->getTitle()][] = $message->getMessage();
        }
        return $return;
    }
    
    private function _resolveMessageType(string $messageType) {
        /* Bootstrap 5.x alert classes
            alert-primary
            alert-secondary
            alert-success
            alert-danger
            alert-warning
            alert-info
            alert-light
            alert-dark
         */
        $map = [
            'default'     => 'info',
            'information' => 'info',
            'warn'        => 'warning',
            'error'       => 'danger',
        ];
        $lcType = strtolower(trim($messageType)) ;
        return isset($map[$lcType]) ? $map[$lcType] : $messageType;
    }
}