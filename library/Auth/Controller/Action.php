<?php
class Auth_Controller_Action extends Skookum_Controller_Action
{

    /**
     * Holds an instance of Zend_Auth.
     *
     * @var Zend_Auth
     */
    protected $_auth;

    /**
     * Default initializer.
     *
     * @access  public
     * @return  void
     */
    public function init()
    {
        parent::init();

        // check user authentication
        $this->_setupAuth();

        // set the default admin layout
        $this->setLayout('admin');
    }

    /**
     * Verify the user has access. Redirect the user to a given
     * URL if no access.
     *
     * @access  protected
     * @param   string      $redirect
     * @return  void
     */
    protected function verifyAcl($redirect = NULL)
    {
        if (!$this->_auth->hasIdentity() && !$this->_isAjax) {
            if (!$redirect) {
                $this->_redirect('/user/login/');
            } else {
                $this->_redirect($redirect);
            }
        }

        return true;
    }

    /**
     * Setup some user authentication variables.
     *
     * @access  private
     * @return  void
     */
    private function _setupAuth()
    {
        $this->_auth = Zend_Auth::getInstance();
    }

}