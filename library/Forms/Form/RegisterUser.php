<?php

class Forms_Form_RegisterUser extends Forms_FormBase
{
	
	// whether the user can change roles
	protected $_allowRoleChange;
	
	// whether an invitation code is required
	protected $_tokenRequired = FALSE;

	/**
	 * Override the default constructor to allow role changes.
	 *
	 * @access	public
	 * @param	bool	$allowRoleChange
	 * @return	void
	 */
	public function __construct($allowRoleChange = FALSE, $tokenRequired = FALSE)
	{
		$this->_tokenRequired = $tokenRequired;
		
		parent::__construct();
	}

	/**
	 * Form field and validation setup.
	 *
	 * @access	public
	 * @return	void
	 */
    public function init()
    {
		parent::init();

        $this->setAction('');
		$this->setAttrib("class", "centered relative register_form");

		// add a new path to custom validation
		$this->addElementPrefixPath('Forms_Form_Validate', 'Forms/Form/Validate', 'validate');

        $this->addElement('text', 'firstname', array(
            'decorators' => $this->_standardElementDecorator,
            'label' => 'First Name',
            'required' => true,
			'attribs' => array('class' => 'medium text-input bottom10')
        ));

		$this->addElement('text', 'lastname', array(
            'decorators' => $this->_standardElementDecorator,
            'label' => 'Last Name',
            'required' => true,
			'attribs' => array('class' => 'medium text-input bottom10')
        ));

        $this->addElement('text', 'email', array(
			'filters'    	=> array('StringTrim', 'StringToLower'),
            'validators' 	=> array('EmailAddress'),
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 		=> 'Email',
            'required' 		=> true,
			'attribs' 		=> array('class' => 'medium text-input bottom10')
        ));

		$this->addElement('password', 'password', array(
			'filters'    	=> array('StringTrim'),
			'validators'	=> array(
				array('StringLength', false, array(6, 20, 'messages' => array(
					Zend_Validate_StringLength::TOO_SHORT 	=> 'Your password must be at least 6 characters in length.',
					Zend_Validate_StringLength::TOO_LONG 	=> 'Your password must not exceed 20 characters in length.'
				)))
			),
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 			=> 'Password',
            'required' 		=> true,
			'attribs' 		=> array('class' => 'medium text-input bottom10')
        ));

        $this->addElement('password', 'password_confirm', array(
			'filters'    	=> array('StringTrim'),
			'validators' 	=> array(
				array('Identical', false, array('token' => 'password'))
			),
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 			=> 'Confirm Password',
			'attribs' 		=> array('class' => 'medium text-input'),
			'required' 		=> true
        ));

		$displayFields = array('firstname', 'lastname', 'email', 'password', 'password_confirm');

		// check if the user can edit the role
		if ($this->_allowRoleChange) {
			
			// the default set of user roles
			$options = array(
				Users::ROLE_USER => 'User',
				Users::ROLE_ADMIN => 'Admin',
				Users::ROLE_SUPER => 'Super Admin'
			);
			
			$this->addElement('select', 'role', array(
				'filters'    	=> array('StringTrim'),
				'validators' 	=> array(
					array('Digits', false, array('messages' => array(
						Zend_Validate_Alnum::NOT_DIGITS => 'Your user role does not appear to be valid.',
						Zend_Validate_Alnum::STRING_EMPTY => 'You must select a user role.',
						Zend_Validate_Alnum::INVALID => 'Your user role does not appear to be valid.'
					)))
				),
				'decorators' 	=> $this->_standardElementDecorator,
				'label'			=> 'User Role',
				'attribs'		=> array('class' => 'medium text-input'),
				'multioptions'	=> $options,
				'required' 		=> true,
			));
			
			// add role dropdown to display fields
			$displayFields[] = 'role';
			
		}

		// check if we require a beta token
		if ($this->_tokenRequired) {
			$this->addElement('hidden', 'token', array(
				'filters'    	=> array('StringTrim'),
				'validators' 	=> array(
					array('Alnum', false, array('messages' => array(
						Zend_Validate_Alnum::INVALID => 'Your invitation code does not appear to be valid.',
						Zend_Validate_Alnum::NOT_ALNUM => 'Your invitation code does not appear to be valid.'
					))),
					array('StringLength', false, array('min' => 32, 'messages' => array(
						Zend_Validate_StringLength::TOO_SHORT 	=> 'Your invitation code must be exactly 32 characters.',
						Zend_Validate_StringLength::TOO_LONG	=> 'Your invitation code must not exceed 32 characters.'
					))),
					array('Usertoken', false)
				),
				'required' 		=> true,
			));

			// add to the display group
			$displayFields[] = 'token';
		}

        $this->addDisplayGroup(
            $displayFields, 'entrydata',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );

		$this->addElement('button', 'submit', array(
			'decorators' => $this->_nakedElementDecorator,
			'label' 		=> 'Join Ruckus',
			'attribs' 	=> array('class'=>'large red button', 'type'=>'submit')
		));

		$buttongroup = array('submit');

        $this->addDisplayGroup(
            $buttongroup, 'entrydatasubmit',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );
    }

}
