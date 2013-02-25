<?php
class My_View_Helper_AccessControl extends Zend_View_Helper_Abstract {
    
    /**
     * Stores the request.
     */
    protected $_request;
    
    /**
     * Stores the controller.
     */
    protected $_controller;
    
    /**
     * Stores the action.
     */
    protected $_action;
    
    /**
     * Stores a mapping of view restrictions for certain user roles given
     * a particular environment.
     * 
     * You can specify either an entire controller, a controller and
     * specific actions, or a controller/action pair with a particular
     * key to prevent view access.
     */
    protected $_restrictions = array(
        // guest only applies to generally public pages
        Users::ROLE_GUEST => array(
            'production' => array('petitions' => '*'),
            'staging' => array('petitions' => '*'),
            'development' => array('petitions' => '*'),
            'local' => array('petitions' => '*')
        ),
        Users::ROLE_USER => array(
            // production environment
            'production' => array(
                // controller restrictions
                'petitions' => '*',
                'group' => '*',
                'member' => array(
                    // controller action restrictions
                    'view' => array(
                        // view "key" restrictions
                        'signed-petitions' => 1,
                        'your-ruck' => 1
                    )
                )
            ),
            // staging environment
            'staging' => array(
                'petitions' => '*',
                'group' => '*',
                'member' => array(
                    'view' => array(
                        'signed-petitions' => 1,
                        'your-ruck' => 1
                    )
                )
            ),
            // development environment
            'development' => array(
                'petitions' => '*',
                'group' => '*',
                'member' => array(
                    'view' => array(
                        'signed-petitions' => 1,
                        'your-ruck' => 1
                    )
                )
            ),
            // local environment
            'local' => array(
                'petitions' => '*',
                'group' => '*',
                'member' => array(
                    'view' => array(
                        'signed-petitions' => 1,
                        'your-ruck' => 1
                    )
                )
            )
        )
    );
    
    /**
     * Checks whether the user passes the particular access control check.
     *
     * @access  public
     * @param   string  $key
     * @return  bool
     */
    public function accessControl($key = NULL) {
        // get the controller and action
        if (!$this->_request) {
            $this->_request = Zend_Controller_Front::getInstance()->getRequest();
            $this->_controller = $this->_request->getControllerName();
            $this->_action = $this->_request->getActionName();
        }
        
        // get the user role from the view
        if (!empty($this->view->user) && !empty($this->view->user->role_id)) {
            $role_id = (int) $this->view->user->role_id;
        } else {
            $role_id = Users::ROLE_GUEST;
        }
            
        // iterate down the restrictions chain for access
        if (isset($this->_restrictions[$role_id])) {
            if (isset($this->_restrictions[$role_id][APPLICATION_ENV])) {
                if (isset($this->_restrictions[$role_id][APPLICATION_ENV][$this->_controller])) {
                    // check if disallowed controller access
                    if ($this->_restrictions[$role_id][APPLICATION_ENV][$this->_controller] === '*') {
                        return false;
                    }
                    // check if disallowed action access
                    if (isset($this->_restrictions[$role_id][APPLICATION_ENV][$this->_controller][$this->_action])) {
                        // check for level of action disallow
                        if ($this->_restrictions[$role_id][APPLICATION_ENV][$this->_controller][$this->_action] === '*') {
                            return false;
                        }
                        // check if disallowed key access
                        if ($key !== NULL) {
                            if (isset($this->_restrictions[$role_id][APPLICATION_ENV][$this->_controller][$this->_action][$key])) {
                                return false;
                            }
                        }
                    }
                }
            }
        }
        
        // unknown role id
        else {
            return false;
        }
        
        // all checks passed, they must be good
        return true;
        
    }
    
}

