<?php
class UserController extends Auth_Controller_Action
{

	// store the users model
	protected $Users;

	/**
	 * Initialize all instance fields for needed model objects.
	 *
	 * @access	public
	 * @return	void
	 */
	public function init() {
		parent::init();

		// load the user model
		$this->Users = new Users();
	}

	/**
	 * Default action. Forward user to login page.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
    {
        $this->_forward('login');
    }

	/**
	 * Login action.
	 *
	 * @access	public
	 * @return	void
	 */
    public function loginAction()
    {
		// disable the layout
		$this->disableLayout();

		// load the login form
		$form = new Forms_Form_UserLogin();

		// handle login request
		if ($this->getRequest()->isPost()) {

			// ensure we have a valid token
			if ($this->isCsrfTokenValid()) {

				// if the form was invalid
				if (!$form->isValid($this->getRequest()->getPost())) {
					$this->_helper->layout->getView()->errors = $form->getErrors();
				} else {

					// generate an instance of the email adapter
					$adapter = new Auth_Adapter_Email($this->Users);
					$result = $this->_auth->authenticate($adapter);
					if ($result) {

						// handle result of login attempt
						switch ($result->getCode()) {

							// successful login
							case Zend_Auth_Result::SUCCESS:

								// get user identity
								$user = $this->_auth->getIdentity();

								// update user session data
								$this->setUser($this->Users->getById($user['id']));

								// check for referrer
								$this->referrer = !empty($_SESSION['referrer']) ? $_SESSION['referrer'] : NULL;

								// remove the referrer
								unset($_SESSION['referrer']);

								// handle redirect properly
								$skip = array('/user/login', '/user/register', '/user/forgot', '/user/reset', '/user/logout', '/images');
								if ($this->referrer !== NULL) {
									foreach ($skip as $s) {
										if (strpos($this->referrer, $s) !== false) {
											$this->_redirect('/admin/dashboard/');
											break;
										}
									}
									$this->_redirect($this->referrer);
								} else {
									$this->_redirect('/admin/dashboard/');
								}
								break;

							// invalid login attempt
							case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
								$this->_helper->FlashMessenger(array('error' => 'The login information you entered is incorrect.'));
								break;

							case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
								$this->_helper->FlashMessenger(array('error' => 'The account information you entered could not be found. Please <a href="/user/register/">register</a> for an account to continue.'));
								break;

							// unknown error
							default:
								$this->_helper->FlashMessenger(array('error' => 'An unknown error occurred.'));
								break;
						}

					}

				}

			} else {
				$this->_helper->FlashMessenger(array('error' => 'You session token has expired.'));
			}

		}

		$this->view->title = "Login";
    }

	/**
	 * Handles forgotten passwords.
	 *
	 * @access	public
	 * @return	void
	 */
	public function forgotAction()
	{
		// load the forgot password form
		// $form = new Forms_Form_UserForgotPassword;

		// handle login request
		if ($this->getRequest()->isPost()) {

			// ensure we have a valid token
			if ($this->isCsrfTokenValid()) {

				// ensure they passed an email
				if (empty($_POST['forgot_email'])) {
					$this->_helper->FlashMessenger(array('success' => 'You must supply an email address.'));
					$this->_redirect('/user/forgot/');
				} else if (strpos($_POST['forgot_email'], '@') === FALSE) {
					$this->_helper->FlashMessenger(array('success' => 'You must supply a valid email address.'));
					$this->_redirect('/user/forgot/');
				}

				$AuthUser = new Auth_Model_User();
				$unique_id = $AuthUser->forgot_password($_POST['forgot_email']);
				if ($unique_id) {

					// grab the reply-to address
					$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/global.ini', APPLICATION_ENV);
					$config = $config->toArray();

					// create view object and assign variables
					$text = new Zend_View();
					$text->setScriptPath(APPLICATION_PATH . '/views/scripts/emails/');

					$text->assign('email', $_POST['forgot_email']);
					$text->assign('unique_id', $unique_id);

					// render view
					$body = $text->render('forgot-password.phtml');

					$mail = new Zend_Mail('utf-8');
					$mail->addTo($_POST['forgot_email']);
					$mail->setSubject('Password Reset');
					$mail->setFrom($config['replyToEmail'], $config['replyToName']);
					$mail->setBodyHtml($body);

					if ($mail->send()) {
						$this->_helper->FlashMessenger(array('success' => 'Your password reset request has been sent. You should be receiving an email shortly.'));
						$this->_redirect('/admin/');
					} else {
						$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to process your password request.'));
					}

				} else {
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to process your password request.'));
				}

			} else {
				$this->_helper->FlashMessenger(array('error' => 'You session has expired.'));
			}

		}

		$this->view->title = "Forgot Password";
		$this->disableLayout();
	}

	/**
	 * Page for resetting a user password. Requires a valid key in order
	 * to reset the page properly.
	 *
	 * @access	public
	 * @return	void
	 */
	public function resetAction()
	{
		// validate that we have the necessary unique_id
		if (empty($this->_params['id']) || strlen($this->_params['id']) != 32) {
			$this->_helper->FlashMessenger(array('error' => 'You do not have access to view the previous page. Please verify your URL is correct and try again.'));
			$this->_redirect('/admin/');
		}

		// validate that the unique_id exists and hasn't expired
		$AuthUser = new Auth_Model_User();
		$status_code = $AuthUser->reset_password($this->_params['id']);
		$this->_validateStatusCode($status_code);

		// handle login request
		$request = $this->getRequest();
		if ($request->isPost()) {

			// ensure we have a valid token
			if ($this->isCsrfTokenValid()) {
				// validate the return code
				$status_code = $AuthUser->reset_password($this->_params['id'], $request->getPost());
				$this->_validateStatusCode($status_code);

				// on success
				$this->_helper->FlashMessenger(array('success' => 'Your password has successfully been reset.'));
				$this->_redirect('/admin/');
			} else {
				$this->_helper->FlashMessenger(array('error' => 'You session has expired.'));
			}

		}

		$this->view->id = $this->_params['id'];
		$this->view->title = "Reset Password";
		$this->disableLayout();
	}

	/**
	 * Handle user logout.
	 *
	 * @access	public
	 */
    public function logoutAction()
    {
		// clean out old cache entries
		$cache = $this->getFrontController()->getParam('cache');
		$cache->clean(Zend_Cache::CLEANING_MODE_OLD);

        Zend_Auth::getInstance()->clearIdentity();
		$this->getSession()->unsetAll();
        $this->_redirect('/admin/');
	}

	/**
	 * Validates the password reset.
	 *
	 * @access	protected
	 * @param	mixed		$status_code
	 */
	protected function _validateStatusCode($status_code)
	{
		// expired
		if ($status_code === 1) {
			$this->_helper->FlashMessenger(array('error' => 'Your password reset key has expired. Please try submitting the forgot password form again.'));
			$this->_redirect('/user/forgot/');
		}
		// no match
		else if ($status_code === 2) {
			$this->_helper->FlashMessenger(array('error' => 'You do not have access to view the previous page. Please verify your URL is correct and try again.'));
			$this->_redirect('/user/login/');
		}
		// passwords don't match
		else if ($status_code === 3) {
			$this->_helper->FlashMessenger(array('error' => 'You must fill out both password fields.'));
			$this->_redirect('/user/reset/' . urlencode($this->_params['id']));
		}
		// passwords don't match
		else if ($status_code === 3) {
			$this->_helper->FlashMessenger(array('error' => 'Your passwords must match.'));
			$this->_redirect('/user/reset/' . urlencode($this->_params['id']));
		}
	}

}