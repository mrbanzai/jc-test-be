<?php
/**
 * an element to allow for arbitraty text/html to be injected in to Zend_Form objects
 */
class Forms_Form_Element_Rawtext extends Zend_Form_Element
{
    public function render(Zend_View_Interface $view = null)
    {
        return $this->getValue();
    }
    
    public function isValid($value, $context = null)
    {
        return true;
    }
}
