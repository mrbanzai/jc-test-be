<?php
class Forms_Form_SmallUserLogin extends Forms_FormBase
{

    public function init()
    {
        $this->setAction('/user/login/');
		$this->setAttrib("id", "login_form");

        // Display Group #1 : Credentials
        $this->addElement('text', 'email', array(
			'label'			=> 'Email',
			'filters'    	=> array('StringTrim', 'StringToLower'),
			'validators' 	=> array('EmailAddress'),
            'decorators' 	=> $this->_nakedLabeledElementDecorator
        ));

        $this->addElement('password', 'password', array(
			'label'			=> 'Password',
			'filters'    	=> array('StringTrim'),
			'validators' 	=> array(
				array('StringLength', false, array(6, 20, 'messages' => array(
					Zend_Validate_StringLength::TOO_SHORT => 'Your password must be at least 6 characters in length.',
					Zend_Validate_StringLength::TOO_LONG => 'Your password must not exceed 20 characters in length.',
				)))
			),
            'decorators' 	=> $this->_nakedLabeledElementDecorator
        ));

		$this->addElement('button', 'submit', array(
            'decorators' 	=> $this->_nakedElementDecorator,
			'value' 		=> 'login',
			'label'			=> 'login',
            'attribs' 		=> array('class'=>'medium red button',
									 'type'=>'submit',
									 'id' => 'submit')
        ));

        $this->addDisplayGroup(
            array('email', 'password', 'token', 'submit', 'cancel'), 'userlogin',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_nakedGroupDecorator
            )
        );

    }

}
