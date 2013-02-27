<?php
require_once dirname(__FILE__) . '/../library/Purifier/HTMLPurifier.auto.php';

date_default_timezone_set('America/New_York');
register_shutdown_function('session_write_close');

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

defined('BASE_PATH')
    || define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));

defined('SITE_ROOT')
    || define('SITE_ROOT', 'http://'.$_SERVER['HTTP_HOST'].'/');

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap()
            ->run();

/*
$path = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR);

define('APPLICATION_ENV', (getenv('ENVIRONMENT') ? getenv('ENVIRONMENT') : 'production'));
define('APPLICATION_PATH', BASE_PATH . '/application');

if (APPLICATION_ENV != 'production') {
    error_reporting(E_ALL|E_STRICT);
    ini_set('display_errors', 'on');
}

// Not sure why
$old = get_include_path();
$old = explode(':', $old);
array_pop($old);

// Ensure library/ is on include_path
set_include_path(
    BASE_PATH . '/library' . PATH_SEPARATOR .
    APPLICATION_PATH . '/views/helpers' . PATH_SEPARATOR .
    APPLICATION_PATH . '/models' . PATH_SEPARATOR .
    implode(':', $old)
);

require_once 'Zend/Application.php';

$app = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// bootstrap
$app->bootstrap();

$app->run();
*/