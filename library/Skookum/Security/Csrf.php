<?php
class Skookum_Security_Csrf
{
    protected $session;
    protected $salt = '!_Hl*NzL1';
    protected $timeout = 800;
    protected $disabled = false;

    public function __construct(Zend_Session_Namespace $session = null)
    {
        if ($session) $this->setSession($session);
    }

    /**
     * determine if a given token is valid against the one in the session
     *
     * @access public
     * @param string $token
     * @return boolean
     */
    public function isValid($token)
    {
        $session = $this->getSession();

        $isvalid = false;
        if (in_array($token, (array) $session->alltokens) && $session->timeout >= time()) {
            $isvalid = true;
        }

        $session->alltokens = array();
        $session->token = null;
        $session->timeout = null;

        return $isvalid;
    }

    /**
     * set whether or nor csrf checks should be disabled
     *
     * @access public
     * @return boolean
     */
    public function setDisabled($val)
    {
        $this->disabled = $val;
        return $this;
    }

    /**
     * determine if csrf checks are disabled
     *
     * @access public
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * set the current salt
     *
     * @access public
     * @param string $salt
     * @return Skookum_Security_Csrf
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * get the current salt
     *
     * @access public
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * generate a new token in the session
     *
     * @access public
     * @param Zend_Session_Namespace $session
     * @return Skookum_Security_Csrf
     */
    public function makeToken()
    {
        $session = $this->getSession();
        $session->token = sha1(mt_rand(1, 1000000) . $this->getSalt() . mt_rand(1, 1000000));
        $session->timeout = time() + $this->timeout;

        if(!is_array($session->alltokens)) {
            $session->alltokens = array();
        }

        $session->alltokens[] = $session->token;

        if (count($session->alltokens) > 4) {
            array_shift($session->alltokens);
        }

        return $session->token;
    }

    /**
     * set a session instance
     *
     * @access public
     * @param Zend_Session_Namespace $session
     * @return Skookum_Security_Csrf
     */
    public function setSession(Zend_Session_Namespace $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * get a session instance
     *
     * @access public
     * @return Zend_Session_Namespace
     */
    public function getSession()
    {
        if (!$this->session) $this->session = new Zend_Session_Namespace('csrf');
        return $this->session;
    }

    /**
     * get the current token
     *
     * @static
     * @access public
     * @return string
     */
    public static function getToken()
    {
        return $_SESSION['csrf']['token'];
    }
}