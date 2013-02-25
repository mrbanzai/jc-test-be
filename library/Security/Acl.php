<?php
class Security_Acl extends Zend_Acl
{

	const ROLE_SUPER	= 0;
	const ROLE_ADMIN	= 3;
	const ROLE_USER		= 2;
	const ROLE_GUEST	= 1;

	/**
	 * Access control.
	 *
	 * @access	public
	 * @param	Zend_Auth	$auth
	 * @return	void
	 */
    public function __construct(Zend_Auth $auth)
    {
		$this->_addResources();
		$this->_addRoles();
		$this->_addRules();
    }

	/**
	 * Add resources (controllers).
	 *
	 * @access	private
	 * @return	void
	 */
	private function _addResources()
	{
		// frontend resources
		$this->add(new Zend_Acl_Resource('index'));
		$this->add(new Zend_Acl_Resource('user'));
		$this->add(new Zend_Acl_Resource('error'));
		$this->add(new Zend_Acl_Resource('css'));
		$this->add(new Zend_Acl_Resource('sitemap'));

		// job specific resources
		$this->add(new Zend_Acl_Resource('search'));
		$this->add(new Zend_Acl_Resource('category'));
		$this->add(new Zend_Acl_Resource('city'));
		$this->add(new Zend_Acl_Resource('state'));
		$this->add(new Zend_Acl_Resource('location'));
		$this->add(new Zend_Acl_Resource('jobs'));

		// backend admin resources
		$this->add(new Zend_Acl_Resource('admin'));
		$this->add(new Zend_Acl_Resource('admin_dashboard'));
		$this->add(new Zend_Acl_Resource('admin_users'));
		$this->add(new Zend_Acl_Resource('admin_jobs'));
		$this->add(new Zend_Acl_Resource('admin_applicants'));
		$this->add(new Zend_Acl_Resource('admin_settings'));

		// api resources
		$this->add(new Zend_Acl_Resource('api_categories'));
		$this->add(new Zend_Acl_Resource('api_cities'));
		$this->add(new Zend_Acl_Resource('api_jobs'));
		$this->add(new Zend_Acl_Resource('api_schedules'));
		$this->add(new Zend_Acl_Resource('api_states'));
	}

	/**
	 * Add user roles.
	 *
	 * @access	private
	 * @return	void
	 */
	private function _addRoles()
	{
		// Add Guest Role
        $this->addRole(new Zend_Acl_Role(Security_Acl::ROLE_GUEST));

        // Add User Role extending Guest
        $this->addRole(new Zend_Acl_Role(Security_Acl::ROLE_USER), Security_Acl::ROLE_GUEST);

		// Add Site Admin Role extending User
        $this->addRole(new Zend_Acl_Role(Security_Acl::ROLE_ADMIN), Security_Acl::ROLE_USER);

		// Add Super Role extending Admin
		$this->addRole(new Zend_Acl_Role(Security_Acl::ROLE_SUPER), Security_Acl::ROLE_ADMIN);
	}

	/**
	 * Add rules.
	 *
	 * @access	private
	 * @return	void
	 */
	private function _addRules()
	{
		// allow full access to these controllers
        $this->allow(
			Security_Acl::ROLE_GUEST,
			array(
				'index', 'error', 'category', 'city', 'state', 'location',
				'jobs', 'search', 'css', 'sitemap', 'admin', 'api_categories',
				'api_cities', 'api_jobs', 'api_schedules', 'api_states'
			)
		);

		// only allow limited access to user controller for given actions
		$this->allow(
			Security_Acl::ROLE_GUEST,
			'user',
			array(
				'index', 'register', 'forgot', 'reset', 'login', 'logout'
			)
		);

		// Override inherited rules as needed for user role
        $this->allow(
			Security_Acl::ROLE_USER,
			array(
				'user', 'admin_dashboard', 'admin_settings', 'admin_jobs',
				'admin_applicants'
			)
		);
		$this->allow(Security_Acl::ROLE_USER, 'admin_users', 'edit');

		// Override inherited rules as needed for admin role
		$this->allow(Security_Acl::ROLE_ADMIN, array('admin_users'));

		// Super doesn't need any overriding
		$this->allow(Security_Acl::ROLE_SUPER);
	}

}
