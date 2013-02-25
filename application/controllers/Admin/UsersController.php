<?php
class Admin_UsersController extends Auth_Controller_Action
{

    // load some models
	public $Users;
	public $Roles;
	public $Theme;

	protected $layout;

	/**
	 * Initialize all instance fields for needed model objects.
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
	{
		parent::init();

		$this->layout = Zend_Layout::getMvcInstance();

        $this->Users = new Users();
        $this->Roles = new Roles();
        $this->Theme = new Theme();

        // force "ajaxy" uploads to error gracefully
        $this->_helper->contextSwitch()
            ->addActionContext('upload', 'json')
            ->initContext();
	}

	/**
	 * Default users list view.
	 *
	 * @access  public
	 * @return  void.
	 */
	public function indexAction()
    {
        $this->view->users = $this->Users->getAll();
    }

    /**
     * Create a new user.
     *
	 * @access	public
	 * @return	void
     */
    public function createAction()
    {
		// set view params
		$this->view->title = "Create a New User";
		$this->view->edit = false;

		// offload all of the work
		$this->_update();
    }

    /**
     * Edit a user account.
     *
     * @access  public
     * @return  void
     */
    public function editAction()
    {
		// get the job id
		$user_id = isset($this->_params['id']) ? $this->_params['id'] : null;

		// set view params
		$this->view->title = "Edit User";
		$this->view->edit = true;

		// offload all of the work
		$this->_update($user_id);
    }

    /**
     * Enable deletions of user accounts if the user has the proper access.
     *
     * @access  public
     * @return  void
     */
	public function deleteAction()
	{
		if (empty($this->_params['id']) || !is_numeric($this->_params['id'])) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a valid user account to delete.'));
			$this->_redirect('/admin/users/');
		}

		// the user to delete
		$delete_user_id = (int) $this->_params['id'];
		$delete_user = $this->Users->getById($delete_user_id);
		if (!$delete_user) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a valid user account to delete.'));
			$this->_redirect('/admin/users/');
		}

		// a map of user roles to the changeable roles
		$roleMap = array(
			Users::ROLE_ADMIN => array(Users::ROLE_USER => 'Client'),
			Users::ROLE_SUPER => array(Users::ROLE_USER => 'Client', Users::ROLE_ADMIN => 'Admin')
		);

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// determine if the user can delete other users
		$canDeleteUser = false;
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
		if (!$isAdmin) {
			$this->_helper->FlashMessenger(array('error' => 'You are not authorized to delete user accounts.'));
			$this->_redirect('/admin/dashboard/');
		}

		// only let admins delete user's of lesser roles
		if ($role_id > $delete_user['role']) {
			$this->_helper->FlashMessenger(array('error' => 'You are not authorized to delete users with greater privileges.'));
			$this->_redirect('/admin/users/');
		}

		// attempt to delete
		if ($this->Users->delete($delete_user_id)) {
			$this->_helper->FlashMessenger(array('success' => 'You have successfully deleted the user ' . Clean::xss($delete_user['firstname'] . ' ' . $delete_user['lastname']) . '.'));
		} else {
			$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to delete ' . Clean::xss($delete_user['firstname'] . ' ' . $delete_user['lastname']) . '.'));
		}

		// redirect back
		$this->_redirect('/admin/users/');
	}

	/**
	 * Wrapper for both add and edit user.
	 *
	 * @access	private
	 * @param	mixed	$editing_user
	 */
	private function _update($editing_user = null)
	{
		// a map of user roles to the changeable roles
		$roleMap = array(
			Users::ROLE_ADMIN => array(Users::ROLE_USER => 'Client'),
			Users::ROLE_SUPER => array(Users::ROLE_USER => 'Client', Users::ROLE_ADMIN => 'Admin')
		);

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// determine if the user can change roles
		$allowableRoles = array();
		$canChangeRole = false;
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));

		// disable role changing on self
		if ($isAdmin) {
			$canChangeRole = true;
			$allowableRoles = $roleMap[$role_id];
		}

		// whether we're using the default subdomain (single install)
		$useDefaultSubdomain = isset($this->_global['useDefaultSubdomain']) ? (bool) $this->_global['useDefaultSubdomain'] : false;

		// remove client role selection if we're using the default subdomain
		// and a client already exists
		if ($useDefaultSubdomain) {
			// check if client exists
			$clientExists = $this->Users->checkIfClientExists($editing_user);
			if ($clientExists && isset($allowableRoles[Users::ROLE_USER])) {
				unset($allowableRoles[Users::ROLE_USER]);
			}
		}

		// grab user info for edits
		if ($editing_user != null) {
			// only let admins edit other users
			if (!$isAdmin && $editing_user != $user_id) {
				$this->_helper->FlashMessenger(array('error' => 'You are not authorized to modify that user account.'));
				$this->_redirect('/admin/users/');
			}

			// looks like we have access to modify the user
			$userinfo = $this->Users->getById($editing_user);

			// grab feed data
			$Ats_Feed = new Ats_Feed();
			$ats = $Ats_Feed->getByUserId($editing_user);

			// retrieve the current logged in user
			$atsFeedId = $ats['id'];
		} else {
			// no ats feed exists
			$atsFeedId = null;
		}

        // populate the form if we aren't updating
        if ($this->getRequest()->isPost()) {
			$this->_handlePostRequest($user, $editing_user, $isAdmin, $canChangeRole, $allowableRoles, $atsFeedId);

			// merge the post data if we didn't redirect already
			if (!isset($userinfo)) {
				$userinfo = array(
					'id' => null,
					'firstname' => null,
					'lastname' => null,
					'company' => null,
					'subdomain' => null,
					'cname' => null,
					'email' => null,
					'role' => null,
					'dynamic_phone_tracking' => null,
					'default_phone' => null
				);
			}

			if (!isset($ats) || !$ats) {
				$ats = array(
					'feed_type_id' => null,
					'user_id' => null,
					'name' => null,
					'url' => null,
				);
			}

			$userinfo = array_merge($userinfo, array_intersect_key($_POST, $userinfo));
			$ats = array_merge($ats, array_intersect_key($_POST, $ats));
		}

		// for retrieving ats types
		$Ats_Type = new Ats_Type();
		$this->view->title = "Create a New User";
		$this->view->userinfo = isset($userinfo) ? $userinfo : null;
		$this->view->ats = isset($ats) ? $ats : null;
		$this->view->atsTypes = $Ats_Type->getAllForDropdown();
		$this->view->modalStyles = array(
				null, // default
				'apply', // upload resume, forward to URL, etc.
				'phone', // provide phone number
				'none' // forward directly to job URL
			);
		$this->view->canChangeRole = $canChangeRole;
		$this->view->allowableRoles = $allowableRoles;
		$this->view->useDefaultSubdomain = $useDefaultSubdomain;

		// for describing subdomain vs. cname
		$this->view->host = !empty($_SERVER['HTTP_HOST']) ? parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) : null;

		// override the view file
		$this->_helper->viewRenderer('update');
	}

	/**
	 * Handles POST requests for add/edit user.
	 *
	 * @access	private
	 * @param	array	$user				The user doing the editing
	 * @param	int		$editing_user		The user currently being edited
	 * @param	bool	$isAdmin
	 * @param	bool	$canChangeRole
	 * @param	mixed	$allowableRoles
	 * @param	mixed	$atsFeedId
	 */
	private function _handlePostRequest($user, $editing_user, $isAdmin, $canChangeRole, $allowableRoles, $atsFeedId)
	{
		// the post data
		$post = $this->getRequest()->getPost();

		// ensure valid CSRF token
		if ($this->isCsrfTokenValid()) {

			// the user we are editing (as determined by post data)
			$edit_user_id = isset($post['id']) ? (int) $post['id'] : null;

			// verify the user we're editing matches the uri
			if (!empty($editing_user) && $editing_user != $edit_user_id) {
				$this->_helper->FlashMessenger(array('error' => 'The user you are attempting to edit does not match the current url.'));
				return false;
			}

			// lock down editing based on ACL
			if (!$isAdmin && ($user['id'] !== $edit_user_id)) {
				$this->_helper->FlashMessenger(array('error' => 'You are only permitted to modify your own personal account settings.'));
				return false;
			} else if ($isAdmin) {
				// TBD - admins can't edit supers
			}

			// ensure the changed role is in the set of allowable roles
			if (!empty($post['role']) && !isset($allowableRoles[$post['role']])) {
				$this->_helper->FlashMessenger(array('error' => 'You are not permitted to change the user role in that manner.'));
				return false;
			}

			// handle the case of add vs. edit
			if ($edit_user_id) {
				$this->_handleEditRequest($post, $editing_user, $isAdmin, $canChangeRole, $atsFeedId);
			} else {
				$this->_handleAddRequest($post, $user['id'], $isAdmin, $canChangeRole);
			}

		} else {
			$this->_helper->FlashMessenger(array('error' => 'Your session token has expired.'));
		}
	}

	/**
	 * Handles add job requests.
	 *
	 * @access	public
	 * @param	array	$post
	 * @param	int		$created_by
	 * @param	bool	$isAdmin
	 * @param	bool	$canChangeRole
	 */
	private function _handleAddRequest($post, $created_by, $isAdmin, $canChangeRole)
	{
		// ensure the user doesn't already exist
		if (!$this->Users->exists($post['email'])) {

			try {

				// attempt to create the user
				$user_id = $this->Users->create($post, $created_by, $canChangeRole);
				if ($user_id !== FALSE) {

					// add the associated ATS information, if applicable
					if ($isAdmin && !empty($post['ats_type_id'])) {

						$AtsFeed = new Ats_Feed();
						if ($AtsFeed->update($post, $user_id)) {
                            $this->Theme->create($user_id);
							// add a success message
							$this->_helper->FlashMessenger(array('success' => 'Your new user account has successfully been created.'));
							$this->_redirect('/admin/users/index/');
						} else {
							// add an error message
							$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to save your ATS information.'));
						}

					} else {
						// add a success message
						$this->_helper->FlashMessenger(array('success' => 'Your new user account has successfully been created.'));
						$this->_redirect('/admin/users/index/');
					}

				} else {
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to create the user. We apologize for the inconvenience.'));
				}

			} catch (Skookum_Form_Validator_Exception $e) {
				$this->view->message = 'An error occurred attempting to create a new user account.';
				$this->_handleFormError($e, 'updateUser');
			}

		} else {
			$this->_helper->FlashMessenger(array('error' => 'The email address specified already exists.'));
		}
	}

	/**
	 * Handles edit job requests.
	 *
	 * @access	public
	 * @param	int		$job_id
	 * @param	array	$post
	 * @param	int		$editing_user
	 * @param	bool	$isAdmin
	 * @param	bool	$canChangeRole
	 * @param	int		$atsFeedId
	 */
	private function _handleEditRequest($post, $editing_user, $isAdmin, $canChangeRole, $atsFeedId)
	{
		try {

			// attempt to edit the user (creates new salt)
			if ($this->Users->update($post, $salt='', $canChangeRole)) {

				// add the associated ATS information, if applicable (only admins)
				if ($isAdmin && !empty($post['ats_type_id'])) {
 				if (!empty($post['ats_type_id'])) {
							if (!empty($post['ats_override_now']) && $post['ats_override_now']) {
								$modalStyle = trim($post['ats_default_modal_style']);
								$AtsJob = new Ats_Job();
								$AtsJob->overrideModalStyle($modalStyle ?: null, $atsFeedId);
							}
					}

					$AtsFeed = new Ats_Feed();
					if ($AtsFeed->update($post, $editing_user, $atsFeedId)) {
						// add a success message
						$this->_helper->FlashMessenger(array('success' => 'The user account has successfully been modified.'));
						$this->_redirect('/admin/users/index/');
					} else {
						// add an error message
						$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to save your ATS information.'));
					}

				} else {
					// add a success message
					$this->_helper->FlashMessenger(array('success' => 'The user account has successfully been modified.'));
					$this->_redirect('/admin/users/index/');
				}

			} else {
				$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to modify the user. We apologize for the inconvenience.'));
			}

		} catch (Skookum_Form_Validator_Exception $e) {
			$this->view->message = 'An error occurred attempting to create a new user account.';
			$this->_handleFormError($e, 'updateUser');
		}
	}

}
