<?php
class Admin_SettingsController extends Auth_Controller_Action
{

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
	}

	/**
	 * Forward the user on to the theme customization page.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
		$this->_redirect('/admin/settings/theme/');
	}

	/**
	 * For displaying and resetting API keys if the client has them enabled.
	 *
	 * @access	public
	 * @return	void
	 */
	public function apiAction()
	{
		// check for a client id
		$client_id = !empty($this->_params['id']) ? $this->_params['id'] : null;

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));

		// verify API access is allowed
		if (empty($this->_global['enableApi'])) {
			$this->_helper->FlashMessenger(array('error' => 'API access has been disabled.'));
			if ($isAdmin) {
				return $this->_redirect('/admin/users/');
			} else {
				return $this->_redirect('/admin/dashboard/');
			}
		}

		// load the model
		$this->rest = new Skookum_Api_Server_Model();

		// allow admin to act on behalf of a client
		if ($isAdmin && empty($client_id)) {
			$this->_helper->FlashMessenger(array('error' => 'You may only view/modify API details of valid clients.'));
			return $this->_redirect('/admin/users/');
		} else if ($isAdmin) {
			// the user id should be changed to the client id
			$user_id = $client_id;
			// grab pertinent user data
			$Users = new Users();
			$user = $Users->getById($user_id);
			if (!$user || !isset($user['role']) || $user['role'] != Users::ROLE_USER) {
				$this->_helper->FlashMessenger(array('error' => 'You may only view/modify API details of valid clients.'));
				return $this->_redirect('/admin/users/');
			}
		}

		// handle post requests
		$request = $this->getRequest();
		if ($request->isPost()) {
			$post = $request->getPost();

			// check for the type of request
			if (isset($post['btnGenerate'])) {
				// attempt to generate a new key-pair
				if ($this->rest->generateKeyPair($post['domain'], $user_id)) {
					$this->_helper->FlashMessenger(array('success' => 'You have successfully generated the key pair.'));
				} else {
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to generate the key pair. Please update your client libraries with these values.'));
				}
			} else if (isset($post['btnRegenerate'])) {
				if ($this->rest->regenerateKeyPair($post['domain'], $user_id)) {
					$this->_helper->FlashMessenger(array('success' => 'You have successfully regenerated the key pair. Please update your client libraries with these new values.'));
				} else {
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to regenerate the key pair.'));
				}
			} else if (isset($post['btnChangeDomain'])) {
				if ($this->rest->updateDomain($post['domain'], $user_id)) {
					$this->_helper->FlashMessenger(array('success' => 'You have successfully updated the domain.'));
				} else {
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to update the domain.'));
				}
			}
		}

		// check for pertinent details
		$this->view->apikeys = $this->rest->getByUserId($user_id);
		$this->view->client_id = $client_id;
	}

	/**
	 * Update the site settings (display).
	 *
	 * @access	public
	 * @return	void
	 */
	public function themeAction()
	{
		// check for a client id
		$client_id = !empty($this->_params['id']) ? $this->_params['id'] : null;

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));

		// allow theming on behalf of a client
		if ($isAdmin && empty($client_id)) {
			$this->_helper->FlashMessenger(array('error' => 'You may only edit the theme of valid clients.'));
			return $this->_redirect('/admin/users/');
		} else if ($isAdmin) {
			// the user id should be changed to the client id
			$user_id = $client_id;
			// grab pertinent user data
			$Users = new Users();
			$user = $Users->getById($user_id);
			if (!$user || !isset($user['role']) || $user['role'] != Users::ROLE_USER) {
				$this->_helper->FlashMessenger(array('error' => 'You may only edit the theme of valid clients.'));
				return $this->_redirect('/admin/users/');
			}
		}

		// grab the client's theme
		$theme = $this->Theme->getByUserId($user_id);
		if (!$theme && !$isAdmin) {
			// create the theme for the client
			$this->Theme->create($user_id);
			$theme = $this->Theme->getByUserId($user_id);
		}

        // handle post requests (add/edit)
        if ($this->getRequest()->isPost()) {
			$this->_handlePostRequest($user, $isAdmin);

			if (!isset($theme)) {
				$theme = array(
					'id' => null,
					'created_by' => null,
					'company' => null,
					'website' => null,
					'logo' => null,
					'bgcolor' => null,
					'bgcolor_hover' => null,
					'fgcolor' => null,
					'link' => null,
					'link_hover' => null,
					'bgbutton' => null,
					'fgbutton' => null,
					'heading' => null
				);
			}

			$theme = array_merge($theme, array_intersect_key($_POST, $theme));
		}

		// view data
		$this->view->isAdmin = $isAdmin;
		$this->view->settings = isset($theme) ? $theme : null;
	}

	/**
	 * Handles POST requests for add/edit theme.
	 *
	 * @access	private
	 * @param	array	$user
	 * @param	bool	$isAdmin
	 */
	private function _handlePostRequest($user, $isAdmin)
	{
		// the post data
		$post = $this->getRequest()->getPost();

		// ensure valid CSRF token
		if ($this->isCsrfTokenValid()) {

			try {

				// check the image upload
				$filepath = $this->_checkDimensions();
				if ($filepath !== FALSE) {
					$post['logo'] = $filepath;
				}

				// ensuring we submit on behalf of the appropriate user
				$post['created_by'] = !$isAdmin ? $user['id'] : $post['created_by'];

				// perform the update
				if ($this->Theme->addEdit($post)) {
					// add a success message
					$this->_helper->FlashMessenger(array('success' => 'The site theme has successfully been updated.'));
				} else {
					// add an error message
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to update the site theme.'));
				}

			} catch (Skookum_Form_Validator_Exception $e) {
				$this->view->message = 'An error occurred attempting to update your theme.';
				$this->_handleFormError($e, 'updateTheme');
			} catch (Exception $e) {
				$e = new Skookum_Form_Validator_Exception($e->getMessage(), array('logo' => $e->getMessage()));
				$this->view->message = 'An error occurred attempting to update your theme.';
				$this->_handleFormError($e, 'updateTheme');
			}

		} else {
			$this->_helper->FlashMessenger(array('error' => 'Your session token has expired. Please try again.'));
		}
	}

	/**
	 * Validate an image upload.
	 *
	 * @access	private
	 */
	private function _checkDimensions()
	{
		// check if we have a logo
		if (!empty($_FILES['logo']['name'])) {
			// check for errors
			if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
				switch($_FILES['logo']['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$msg = 'The file you are attempting to upload exceeds the maximum allowable filesize.';
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$msg = 'The file you are attempting to upload exceeds the maximum allowable filesize.';
						break;
					case UPLOAD_ERR_PARTIAL:
						$msg = 'The uploaded file was only partially uploaded.';
						break;
					case UPLOAD_ERR_NO_FILE:
						$msg = 'No file was uploaded.';
						break;
					UPLOAD_ERR_NO_TMP_DIR:
						$msg = 'Your file could not be uploaded due to a missing temporary folder.';
						break;
					UPLOAD_ERR_CANT_WRITE:
						$msg = 'An error occurred attempting to write your file to disk.';
						break;
					UPLOAD_ERR_EXTENSION:
						$msg = 'The file upload was stopped for an unknown reason.';
						break;
				}
				throw new Exception($msg);
			}

			// check type
			$ext = strtolower(substr($_FILES['logo']['name'], strrpos($_FILES['logo']['name'], '.') + 1));
			if (!in_array($ext, array('png', 'gif', 'jpg', 'jpeg'))) {
				throw new Exception('Your logo must be of type gif, jpg, jpeg, or png.');
			}

			// ensure we can see the file
			if (!is_readable($_FILES['logo']['tmp_name'])) {
				throw new Exception('We could not open your logo for validating it\'s contents.');
			}

			// check dimensions
			$size = @getimagesize($_FILES['logo']['tmp_name']);
			if (empty($size) or ($size[0] === 0) or ($size[1] === 0)) {
				throw new Exception('We were unable to determine the dimensions of your logo.');
			}

			// check width
			if ($size[0] > 890) {
				throw new Exception('Your logo must not exceed the maximum allowable dimensions.');
			} else if ($size[1] > 400) {
				throw new Exception('Your logo must not exceed the maximum allowable dimensions.');
			}

			// create a new file
			$filename = md5(mt_rand(0, time())) . '-' . time() . '.' . $ext;
			$filepath = BASE_PATH . '/public/uploads/' . $filename;
			if (move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
				@chmod($filepath, 0755);
				return '/uploads/' . $filename;
			}

		}

		return false;
	}

}
