<?php
class Auth_Adapter_Email implements Zend_Auth_Adapter_Interface
{
    /**
     * User model storage.
     *
     * @var object
     */
    protected $_users;

    /**
     * Data storage.
     */
    protected $_data = null;

    /**
     * Constructor, loads and returns the adapter.
     *
     * @access  public
     */
    public function __construct(Users $users)
    {
        $this->_users = $users;
    }

    /**
     * Set data.
     *
     * @access  public
     * @param   array   $data
     * @return  &$this
     */
    public function setData(array $data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Retrieves data.
     *
     * @access  public
     * @return  array
     */
    public function getData()
    {
        // if we don't have data, set as the request post data
        if (is_null($this->_data)) {
            $frontController = Zend_Controller_Front::getInstance();
            $request = $frontController->getRequest();
            $this->_data = $request->getPost();
        }

        return $this->_data;
    }

    /**
     * Authenticates the user with an email/password combination.
     * Defined by Zend_Auth_Adapter_Interface.
     *
     * @throws Zend_Auth_Adapter_Exception If answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        // retrieve post data if not set for grabbing login credentials
        $post = $this->getData();

        // verify the user credentials
        $user = $this->_users->authenticate($post);
        if ($user !== FALSE) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $user);
        }

        // return an error
        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE,
            NULL,
            array('error' => 'An error occurred while attempting to authenticate.')
        );
    }

}