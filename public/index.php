<?php
require_once dirname(__FILE__) . '/../library/Purifier/HTMLPurifier.auto.php';

date_default_timezone_set('America/New_York');
register_shutdown_function('session_write_close');

$path = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR);

define('APPLICATION_ENV', (getenv('ENVIRONMENT') ? getenv('ENVIRONMENT') : 'production'));
define('BASE_PATH', rtrim(substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR) + 1), DIRECTORY_SEPARATOR));
define('SITE_ROOT', 'http://'.$_SERVER['HTTP_HOST'].'/');
define('APPLICATION_PATH', BASE_PATH . '/application');

if (APPLICATION_ENV != 'production') {
    error_reporting(E_ALL|E_STRICT);
    ini_set('display_errors', 'on');
}

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

// run if not a cronjob
if (!defined('IS_CRONJOB') || IS_CRONJOB == false) {
    $app->run();
} else {

    // CLI specific
    $getopt = new Zend_Console_Getopt(
        array(
        'action|a=s' => 'action to perform in format of "module/controller/action"',
        'help|h'     => 'displays usage information',
        'list|l'     => 'List available jobs',
        )
    );

    try {
        $getopt->parse();
    } catch (Zend_Console_Getopt_Exception $e) {
        // Bad options passed: report usage
        echo $e->getUsageMessage();
        return false;
    }

    if ($getopt->getOption('l')) {
        // add help messages..
    }

    if ($getopt->getOption('h')) {
        echo $getopt->getUsageMessage();
        return true;
    }

    if ($getopt->getOption('a')) {

        $front = $application->getBootstrap()->getResource('frontcontroller');
        $params = array_reverse(explode('/', $getopt->getOption('a')));
        $module = array_pop($params);
        $controller = array_pop($params);
        $action = array_pop($params);

        if (count($params)) {
            foreach ($params as $param) {
                $splitedNameValue = explode('=', $param);
                $passParam[$splitedNameValue[0]] = $splitedNameValue[1];
            }
        } else {
            $passParam = array();
        }

        $request = new Zend_Controller_Request_Simple($action, $controller, $module, $passParam);

        $front->setRequest($request)
            ->setResponse(new Zend_Controller_Response_Cli())
            ->setRouter(new Nc_Controller_Router_Cli());

        $app->run();

    }

}
