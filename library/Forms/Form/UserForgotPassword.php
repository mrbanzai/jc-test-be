<?php

class Forms_Form_UserForgotPassword extends Forms_FormBase
{

    public function init()
    {
        $this->setAction('/user/forgot/');

        // Display Group #1 : Credentials
        $this->addElement('text', 'forgot_email', array(
			'filters'    	=> array('StringTrim', 'StringToLower'),
            'validators' 	=> array('EmailAddress'),
            'decorators' 	=> $this->_standardElementDecorator,
			'label' 		=> 'Email',
			'attribs' 		=> array('class' => 'medium text-input bottom10'),
            'required' 		=> true
        ));

        $this->addDisplayGroup(
            array('forgot_email'), 'forgot',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );

        $this->addElement('button', 'submit', array(
            'decorators' 	=> $this->_nakedElementDecorator,
			'value' 		=> 'login',
            'attribs' 		=> array('class'=>'medium red button', 'type'=>'submit')
        ));

        $this->addDisplayGroup(
            array('submit'), 'request reset',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator,
                'class' => 'submit' // fieldset class attribute for some later styling
            )
        );

    }

}
