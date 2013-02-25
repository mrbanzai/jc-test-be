<?php
/**
 * Bootstrap class
 * class sets up environment including constants and include path,
 * then nitializes needed resources.
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected $_session;
    protected $_front;
    protected $_loader;

	protected $csrf;

    /**
     * run startup items
     *
     * @access protected
     * @return void
     */
    protected function _initStartup()
    {
        $this->_loader = Zend_Loader_Autoloader::getInstance();
        $this->_loader->registerNamespace(array('Skookum_', 'Bayard_'));
        $this->_loader->setFallbackAutoloader(true);

        if (APPLICATION_ENV != 'production') {
            $this->_loader->suppressNotFoundWarnings(false);
        } else {
            $this->_loader->suppressNotFoundWarnings(true);
        }

        // ensure front controller instance is present, and fetch it
        if (!$this->_front) {
            $this->bootstrap('FrontController');
            $this->_front = $this->getResource('FrontController');
        }

        $this->_front->setParam('csrf', new Skookum_Security_Csrf());

        date_default_timezone_set('America/New_York');
    }

    /**
     * Add a file logger to the registry.
     *
     * @access  protected
     * @return  void
     */
    protected function _initLogger()
    {
		if (APPLICATION_ENV != 'production') {
			$logdir = APPLICATION_PATH . '/data/logs';
			if (!is_dir($logdir)) {
				mkdir($logdir, 0755, true);
			}

			$writer = new Zend_Log_Writer_Stream($logdir . '/error.log');
			Zend_Registry::set('Logger', new Zend_Log($writer));
		}
    }

    /**
     * load configuration files
     *
     * @access protected
     * @return void
     */
    protected function _initConfig()
    {
		// load the application.ini
        $configRegistry = Zend_Registry::getInstance();
        $configRegistry->set('application', new Zend_Config($this->getOptions()));

		// load the router
		$router = $this->_front->getRouter();

		// get the routes config
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini', APPLICATION_ENV);

		// add to router
		$router->addConfig($config, 'routes');

		// get the global config
		$global = new Zend_Config_Ini(APPLICATION_PATH . '/configs/global.ini', APPLICATION_ENV);

		// make configs available
        $this->_front->setParam('config', $configRegistry);
        Zend_Registry::set('config', $configRegistry);
		Zend_Registry::set('global', $global->toArray());
    }

    /**
     * assign some generic values to the view
     *
     * @access protected
     * @return void
     */
    protected function _initViewSetup()
    {
        $view = $this->bootstrap('view')->getResource('view');
        $view->environment = APPLICATION_ENV;
        $view->applicationPath = APPLICATION_PATH;
    }

	/**
	 * Initialize caching functionality.
	 */
	protected function _initCache()
	{
		$frontendOptions = array(
			'automatic_serialization' => true,
			'lifetime' => 1800
		);

		$backendOptions  = array(
			'cache_dir' => APPLICATION_PATH . '/data/cache'
		);

		// ensure the cache directory exists
		if (!is_dir($backendOptions['cache_dir'])) {
			mkdir($backendOptions['cache_dir'], 644, true);
		}

		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

		$this->_front->setParam('cache', $cache);

		// cache db metadata
		Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
	}

	/**
	 * Initialize view helpers.
	 */
    protected function _initHelperPath() {
        $view = $this->bootstrap('view')->getResource('view');
        $view->addHelperPath(APPLICATION_PATH . '/views/helpers', 'My_View_Helper');
    }

	/**
	 * Initialize and register ACL plugin
	 */
	protected function _initAcl()
    {
		$auth 	= Zend_Auth::getInstance();
        $acl 	= new Security_Acl($auth);

        $this->_front->setParam('auth', $auth);
        $this->_front->setParam('acl', $acl);
        $this->_front->registerPlugin(
            new Security_Controller_Plugin_Acl($auth, $acl)
        );
    }

    /**
     * setup default db adapter from config file as well as determine
     * if we need to enable the database profiler. The db profiler
     * logs to the firebug console using the FirePHP extension.
     */
	protected function _initDbAdapter()
    {
        $this->bootstrap('db');
        $dbAdapter = $this->getResource('db');

		// determine if we should use the profiler
		if (APPLICATION_ENV != 'production') {
			$profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
			$profiler->setEnabled(true);
			$dbAdapter->setProfiler($profiler);
		}

        // cache db metadata
        Zend_Db_Table_Abstract::setDefaultMetadataCache($this->_cache);

		// add the dbAdapter to the registry
        Zend_Registry::set('dbAdapter', $dbAdapter);

		// load the session handler
		$this->_setSessionSaveHandler($dbAdapter);
    }

	/**
	 * Initialize user object if logged in
	 */
	protected function _initUser()
    {
		// bootstrap the view
		$this->bootstrap('view');

		// load the view resource
		$view = $this->getResource('view');

        // only do the gruntwork if we're logged in
        $identity = @Zend_Auth::getInstance()->getIdentity();
        if ($identity && empty($this->_session->user)) {

            // load the model
            $Users = new Users();

            // set user object
            $user = (object) $Users->getDetails($identity);

            // update the session
            $this->_session->user = $user;

			// mark as logged in
			$loggedIn = true;

			// determine the role
			$isAdmin = in_array($user->role, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
			$isSuper = (int) $user->role === Users::ROLE_SUPER;

            // add visit timestamp to user profile
            $Users->addVisitWithId($user->id);

        } else if (!empty($this->_session->user)) {

            // load the model
            $Users = new Users();

			// grab user data from session
            $user = (object) $this->_session->user;

			// mark as logged in
			$loggedIn = true;

			// determine the role
			$isAdmin = in_array($user->role, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
			$isSuper = (int) $user->role === Users::ROLE_SUPER;

            // add visit timestamp to user profile
            $Users->addVisitWithId($user->id);

        } else {
            $user = array();
			$loggedIn = false;
			$isAdmin = false;
			$isSuper = false;
        }

		// set the user
		$view->user = $user;
		$view->loggedIn = $loggedIn;
		$view->isAdmin = $isAdmin;
		$view->isSuper = $isSuper;
    }

	/**
	 * Not quite sure what options we're adding here....
	 */
	protected function _initExtendedConfig()
	{
		Zend_Registry::set('imageopts', $this->getOption('images'));
		Zend_Registry::set('emailopts', $this->getOption('email'));

	}

	/**
	 * Continuation of the db adapter handling for sessions.
	 */
	private function _setSessionSaveHandler($dbAdapter)
	{
        // load the custom session handler
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/session.ini', APPLICATION_ENV);

		// set a custom session save handler
        Zend_Session::setSaveHandler(new Skookum_Session_SaveHandler_DbAdapter($dbAdapter, $config->toArray()));

        // store the session namespace object
        $this->_session = new Zend_Session_Namespace('global');
	}
}
