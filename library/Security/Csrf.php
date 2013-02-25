<?php
class Security_Csrf
{
    protected $session;
    protected $salt = '!_Hl*NzL1';
    protected $timeout = 300;
    
    public function __construct(Zend_Session_Namespace $session = null)
    {
        if ($session) $this->setSession($session);
    }
    
    public function isValid($token)
    {
        $session = $this->getSession();
        $tokendata = $session->csrfToken;
        $isvalid = $tokendata['token'] == $token && $tokendata['timeout'] > time();
        $session->csrfToken = null;
        return $isvalid;
    }
    
    public function makeToken()
    {
        $session = $this->getSession();
        $token = md5(mt_rand(1,1000000) . $this->salt . mt_rand(1,1000000));
        $session->csrfToken = array('token' => $token, 'timeout' => time() + $this->timeout);
        return $token;
    }
    
    public function setSession(Zend_Session_Namespace $session)
    {
        $this->session = $session;
        return $this;
    }
    
    public function getSession()
    {
        if (!$this->session) $this->session = new Zend_Session_Namespace('global');
        return $this->session;
    }
    
    public static function generateToken(Zend_Session_Namespace $session = null)
    {
        $csrf = new Security_Csrf($session);
        return $csrf->makeToken();
    }
}