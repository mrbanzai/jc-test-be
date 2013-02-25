<?php
date_default_timezone_set('America/New_York');

// force working out of cron dir
chdir(dirname(__FILE__));

// get the base path
$path = trim(dirname(__FILE__), DIRECTORY_SEPARATOR);
$path = explode(DIRECTORY_SEPARATOR, $path);
array_pop($path);
array_pop($path);
$path = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;

define('BASE_PATH', $path);
define('APPLICATION_PATH', BASE_PATH . 'application');

// get the environment
$env = getenv('ENVIRONMENT');
if (!$env) {
    if (PHP_SAPI == 'cli') {
        $env = isset($argv[1]) ? $argv[1] : 'production';
    } else if (isset($_SERVER['NGINX_ENVIRONMENT'])) {
		$env = $_SERVER['NGINX_ENVIRONMENT'];
	} else {
		$env = 'production';
	}
}
define('APPLICATION_ENV', $env);

$old = get_include_path();
$old = explode(':', $old);
array_pop($old);

// set the include path
set_include_path(
    BASE_PATH . '/library' . PATH_SEPARATOR .
	BASE_PATH . '/library/Zend' . PATH_SEPARATOR .
	APPLICATION_PATH . '/views/helpers' . PATH_SEPARATOR .
	APPLICATION_PATH . '/models' . PATH_SEPARATOR .
	implode(':', $old)
);

// include zend application
require_once 'Zend/Application.php';

// load the bootstrap but dont run
$app = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// bootstrap to get database
$app->bootstrap();
$bootstrap = $app->getBootstrap();
$db = $bootstrap->getResource('db');