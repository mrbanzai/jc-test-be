<?php

class Forms_Form_Decorator_LabelError extends Zend_Form_Decorator_Label
{

    public function getLabel()
    {
        $element = $this->getElement();
        $errors = $element->getMessages();
        
        $label = trim($element->getLabel());
        
        if ($element->isRequired())
            //$label .= ' <span style="color:red">*</span>';
        
        
        if ($errors) {
            $label .= ' <span class="formError"><strong style="color:red">';
            
            if (is_array($errors))
                $label .= array_pop($errors);
            else
                $label .= $errors;
            
            $label .= '</strong></span>';
        }

        $element->setLabel($label);

        return parent::getLabel();
    }

}