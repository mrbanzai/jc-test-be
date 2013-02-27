<?php
/**
 * Custom bootstrap file to handle cronjobs that still take advantage of
 * the full Zend MVC, including routing.
 *
 * @author  Corey Ballou
 */

// ensure we handle this as a cron

require_once dirname(__FILE__) . '/../library/Purifier/HTMLPurifier.auto.php';

define('IS_CRONJOB', true);

date_default_timezone_set('America/New_York');

// set the path to the base directory
$path = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR);
$path = rtrim(substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR) + 1), DIRECTORY_SEPARATOR);

// need to move the include paths a little further up the food chain
$old = get_include_path();
$old = explode(':', $old);
array_pop($old);

define('BASE_PATH', $path);
define('SITE_ROOT', '/');
define('APPLICATION_PATH', BASE_PATH . '/application');

// Ensure library/ is on include_path
set_include_path(
    BASE_PATH . '/library' . PATH_SEPARATOR .
    APPLICATION_PATH . '/views/helpers' . PATH_SEPARATOR .
    APPLICATION_PATH . '/models' . PATH_SEPARATOR .
    implode(':', $old)
);

// CLI specific
$getopt = new Zend_Console_Getopt(
    array(
    'action|a=s'        => 'action to perform in format of "module/controller/action"',
    'environment|e=s'   => 'The current environment',
    'help|h'            => 'displays usage information',
    'list|l'            => 'List available jobs'
    )
);

try {
    $getopt->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    // Bad options passed: report usage
    echo $e->getUsageMessage();
    return false;
}

// get the environment
if ($getopt->getOption('e')) {
    $env = $getopt->getOption('e');
} else {
    $env = 'production';
}

define('APPLICATION_ENV', $env);
if (APPLICATION_ENV != 'production') {
    error_reporting(E_ALL|E_STRICT);
    ini_set('display_errors', 'on');
}

if ($getopt->getOption('l')) {
    // add help messages..
    echo 'ET Phone Home...';
    return true;
}

if ($getopt->getOption('h')) {
    echo $getopt->getUsageMessage();
    return true;
}

if ($getopt->getOption('a')) {

    require_once 'Zend/Application.php';

    $app = new Zend_Application(
        APPLICATION_ENV,
        APPLICATION_PATH . '/configs/cron.ini'
    );

    $front = $app
        ->getBootstrap()
        ->bootstrap()
        ->getResource('FrontController');

    $params = array_reverse(explode('/', $getopt->getOption('a')));
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

    $request = new Zend_Controller_Request_Simple($action, $controller, null, $passParam);

    $front->setRequest($request)
        ->setResponse(new Zend_Controller_Response_Cli())
        ->setRouter(new Skookum_Controller_Router_Cli())
        ->throwExceptions(true);

    $app->bootstrap()->run();

} else {
    echo 'You need to specify a controller';
    return true;
}