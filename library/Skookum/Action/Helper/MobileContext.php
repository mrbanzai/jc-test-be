<?php

/**
 * @link http://juriansluiman.nl/en/article/111/mobile-detection-with-zend_http_useragent
 */
class Skookum_Action_Helper_MobileContext extends Zend_Controller_Action_Helper_ContextSwitch
{
    /**
     * Flag to enable layout based on WURFL detection
     * @var bool
     */
    protected $_enabled = false;

    /**
     * Controller property to utilize for context switching
     * @var string
     */
    protected $_contextKey = 'mobileable';

    /**
     * Whether or not to disable layouts when switching contexts
     * @var boolean
     */
    protected $_disableLayout = false;

    /**
     * Constructor
     *
     * Add HTML context
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->addContext('html', array('suffix' => 'mobile'));
    }

    /**
     * Enable the mobile contexts
     *
     * @return void
     */
    public function enable ()
    {
        $this->_enabled = true;
    }

     /**
     * Add one or more contexts to an action
     *
     * The context is by default set to 'html' so no additional context is required for mobile
     *
     * @param  string       $action
     * @return Zend_Controller_Action_Helper_ContextSwitch|void Provides a fluent interface
     */
    public function addActionContext($action, $context = 'html')
    {
        return parent::addActionContext($action, $context);
    }

    /**
     * Initialize AJAX context switching
     *
     * Checks for XHR requests; if detected, attempts to perform context switch.
     *
     * @param  string $format
     * @return void
     */
    public function initContext($format = 'html')
    {
        $this->_currentContext = null;

        if (false === $this->_enabled) {
            return;
        }

        return parent::initContext($format);
    }
}