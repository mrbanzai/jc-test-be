<?php

class Forms_Form_UserResetPassword extends Forms_FormBase
{

    public function init()
    {
		$this->addElement('password', 'password', array(
			'filters'    	=> array('StringTrim'),
			'validators'	=> array(
				array('StringLength', false, array(6, 20, 'messages' => array(
					Zend_Validate_StringLength::TOO_SHORT 	=> 'Your password must be at least 6 characters in length.',
					Zend_Validate_StringLength::TOO_LONG 	=> 'Your password must not exceed 20 characters in length.'
				)))
			),
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 		=> 'Password',
			'attribs' 		=> array('class' => 'medium text-input bottom10'),
            'required' 		=> true
        ));

        $this->addElement('password', 'password_confirm', array(
			'filters'    	=> array('StringTrim'),
			'validators' 	=> array('Identical'),
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 		=> 'Confirm Password',
			'attribs' 		=> array('class' => 'medium text-input'),
			'required' 		=> true
        ));

        $this->addDisplayGroup(
            array('password', 'password_confirm'), 'entrydata',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );

        // Display Group #2 : Submit
        $this->addElement('button', 'submit', array(
            'decorators' => $this->_nakedElementDecorator,
			'value' => 'login',
            'attribs' => array('class'=>'medium red button', 'type'=>'submit')
        ));

        $this->addDisplayGroup(
            array('submit'), 'entrydatasubmit',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_buttonGroupDecorator,
                'class' => 'submit'
            )
        );
    }

	/**
	 * Handle validation of things like password matching.
	 *
	 * @access		public
	 * @override
	 */
	public function isValid ($data) {
		$passTwice = $this->getElement('password_confirm');
		$passTwice->getValidator('Identical')->setToken($data['password'])->setMessage('Your passwords must match.');
		return parent::isValid($data);
    }
}
