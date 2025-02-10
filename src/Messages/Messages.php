<?php
/**
 * PHP message storage and retrieval trait.
 * 
 */
namespace Procomputer\Pcclib\Messages;

trait Messages {

    /**
     * 
     * @var MessageStore
     */
    private $_messageStore;
    
    /**
     * 
     * @return MessageStore
     */
    private function messageStore() {
        if(! isset($this->_messageStore)) {
            $this->_messageStore = new MessageStore();
        }
        return $this->_messageStore;
    }
    
    /**
     * Saves message or messages.
     * @param string|array|\Traversable|\Throwable|Message $messages Message or messages.
     * @param string $messageType (optional) An alert type eg 'info', 'warning', 'error', 'danger'
     * @param string $title       (optional) Message title else $messageType used.
     * @return self
     */
    public function addMessage(string|array|\Traversable|\Throwable|Message|MessageStore $messages, string $messageType = 'default', string $title = '') {
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
        $this->messageStore()->addMessage($messages, $messageType, $title);
        return $this;
    }
    /**
     * Alias of addMessage
     * @return self
     */
    public function saveMessage(string|array|\Traversable|Message|MessageStore $messages, string $messageType = 'default', string $title = '') {
        return $this->addMessage($messages, $messageType, $title);
    }
    
    /**
     * Returns saved messages.
     * @param string $type (optional) Type of messages to return eg 'error', 'warning'
     * @return array
     */
    public function getMessages(string $type = 'all') : array {
        return $this->messageStore()->getMessages($type);
    }

    /**
     * Returns number of messages.
     * @param string $type (optional) Type of messages to return eg 'error', 'warning'
     * @return array
     */
    public function getMessageCount(string $type = 'all') : int {
        return $this->messageStore()->getMessageCount($type);
    }

    /**
     * Clears messages.
     * @return ServiceCommon
     */
    public function clearMessages() {
        $this->messageStore()->clearMessages();
        return $this;
    }
}