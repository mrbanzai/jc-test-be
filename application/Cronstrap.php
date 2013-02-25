<?php
/**
 * Bootstrap class
 * class sets up environment including constants and include path,
 * then nitializes needed resources.
 */
class Cronstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected $_front;
    protected $_loader;

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

        $this->_front->setParam('config', $configRegistry);
        Zend_Registry::set('config', $configRegistry);
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
     * setup default db adapter from config file as well as determine
     * if we need to enable the database profiler. The db profiler
     * logs to the firebug console using the FirePHP extension.
     */
	protected function _initDbAdapter()
    {
        $this->bootstrap('db');
        $dbAdapter = $this->getResource('db');

        // cache db metadata
        Zend_Db_Table_Abstract::setDefaultMetadataCache($this->_cache);

		// add the dbAdapter to the registry
        Zend_Registry::set('dbAdapter', $dbAdapter);
    }

}
