<?php
class Security_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{

    protected $_auth = null;
    protected $_acl = null;

	/**
	 * Load up auth and ACL.
	 *
	 * @access	public
	 * @param	Zend_Auth	$auth
	 * @param	Zend_Acl	$acl
	 * @return	void
	 */
    public function __construct(Zend_Auth $auth, Zend_Acl $acl)
    {
        $this->_auth = $auth;
        $this->_acl = $acl;
    }

	/**
	 * Pre-dispatch to handle access control checks on the user.
	 *
	 * @access	public
	 * @param	Zend_Controller_Request_Abstract	$request
	 * @return	void
	 */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		$view = Zend_Layout::getMvcInstance()->getView();
		if (isset($view->user->role)) {
			$role = $view->user->role;
		} else {
			$role = Security_Acl::ROLE_GUEST;
		}

        // a resource is simply a controller name
        $resource = $request->getControllerName();
        if (!$this->_acl->has($resource)) {
            $resource = null;
        }

		// get the privilege (controller action)
		$privilege = $request->getActionName();

        // ACL Access Check
        if (!$this->_acl->isAllowed($role, $resource, $privilege)) {

			// add a redirection
			$_SESSION['referrer'] = $request->getRequestUri();

            if ($this->_auth->hasIdentity()) {

                // authenticated, denied access, forward to index
				$helper = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
				$helper->addMessage(array('error' => 'You do not have authorization to view the previous page.'));

				$helper = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
				$helper->gotoUrl('/user/login/')->redirectAndExit();

				return;

            } else {

                // not authenticated, forward to login form
				$helper = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
				$helper->addMessage(array('error' => 'You must be logged in first.'));

				$helper = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
				$helper->gotoUrl('/user/login/')->redirectAndExit();

				return;

            }

        }

    }

}
