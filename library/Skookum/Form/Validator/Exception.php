<?php
class Skookum_Form_Validator_Exception extends Exception {
    
    private $_errors = array();

    public function __construct($message, array $errors = array()) {
        parent::__construct($message, 0, NULL);
        $this->_errors = $errors;
    }

    public function getErrors() {
        return $this->_errors;
    }
    
}