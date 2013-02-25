<?php
class Forms_Form_UserLogin extends Forms_FormBase
{

    public function init()
    {
		parent::init();

        $this->setAction('/user/login/');
		$this->setAttrib("class", "centered relative loginform_large");

        // Display Group #1 : Credentials
        $this->addElement('text', 'email', array(
			'filters'    	=> array('StringTrim', 'StringToLower'),
            'validators' 	=> array('EmailAddress'),
            'decorators' 	=> $this->_standardElementDecorator,
			'label' 		=> 'Email',
			'attribs' 		=> array('class' => 'large text-input bottom10'),
            'required' 		=> true
        ));

        $this->addElement('password', 'passphrase', array(
			'filters'    	=> array('StringTrim'),
			'validators' 	=> array(
				array('StringLength', false, array(6, 20, 'messages' => array(
					Zend_Validate_StringLength::TOO_SHORT => 'Your password must be at least 6 characters in length.',
					Zend_Validate_StringLength::TOO_LONG => 'Your password must not exceed 20 characters in length.',
				)))
			),
            'decorators' 	=> array(
				'ViewHelper',
				array('LabelError', array('escape'=>false)),
				array('HtmlTag', array('tag'=>'li')),
				array('Description', array('escape' => false))
			),
			'description'	=> '<a href="/user/forgot/" style="position:relative;margin-right:20px;float:right;">Forgot Password?</a>',
			'label' 		=> 'Password',
			'attribs' 		=> array('class' => 'large text-input bottom10'),
            'required' 		=> true
        ));

        $this->addDisplayGroup(
            array('email', 'passphrase'), 'userlogin',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );

        $this->addElement('button', 'submit', array(
            'decorators' 	=> $this->_nakedElementDecorator,
			'description' => 'test',
			'value' 		=> 'login',
            'attribs' 		=> array('class'=>'large red button', 'type'=>'submit')
        ));

        $this->addDisplayGroup(
            array('submit'), 'login',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator,
                'class' => 'submit' // fieldset class attribute for some later styling
            )
        );
    }

}
