<?php

class Forms_Form_UserEdit extends Forms_FormBase
{

	// whether the user can change roles
	protected $_allowRoleChange;

	/**
	 * Override the default constructor to allow role changes.
	 *
	 * @access	public
	 * @param	bool	$allowRoleChange
	 * @return	void
	 */
	public function __construct($allowRoleChange = FALSE)
	{
		$this->_allowRoleChange = $allowRoleChange;
		
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
        $this->setAction('/user/account/');

        // Display Group #1 : Entry Data
        $this->addElement('text', 'firstname', array(
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 		=> 'First Name',
			'attribs' 		=> array('class' => 'large text-input bottom10'),
            'required' 		=> true
        ));

		$this->addElement('text', 'lastname', array(
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 		=> 'Last Name',
			'attribs' 		=> array('class' => 'large text-input bottom10'),
            'required' 		=> true
        ));

        $this->addElement('text', 'email', array(
            'decorators' 	=> $this->_standardElementDecorator,
            'label' 		=> 'Email Address',
			'attribs' 		=> array('class' => 'large text-input bottom10'),
            'required' 		=> true
        ));

		/*
		$this->addElement('textarea', 'about', array(
            'decorators' => $this->_standardElementDecorator,
            'label' => 'About Me',
			'attribs' => array('class' => 'large text-input bottom10', 'rows' => '8')
        ));
		*/

        $this->addDisplayGroup(
            array('firstname', 'lastname', 'email', 'about'), 'entrydata',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );

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

		// whether to show or hide your email address
		/*
		$this->addElement('checkbox', 'show_email', array(
            'decorators' => $this->_radioElementDecorator,
			'label' 		=> 'Show Email Address',
			'attribs' 	=> array('class' => 'large bottom10'),
            'value' 		=> 1
        ));

		// privacy settings group
		$this->addDisplayGroup(
            array('show_email'), 'privacy',
            array(
                'disableLoadDefaultDecorators' => true,
                'decorators' => $this->_standardGroupDecorator
            )
        );
		*/

        // Display Group #2 : Submit
        $this->addElement('submit', 'submit', array(
            'decorators' => $this->_nakedElementDecorator,
            'label' => 'Submit',
			'attribs' => array('class' => 'large red button')
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

}