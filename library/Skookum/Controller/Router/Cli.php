<?php
/**
 * A dummy router for usage with the command line and cronjobs.
 *
 * @author  Corey Ballou
 */
class Skookum_Controller_Router_Cli
    extends Zend_Controller_Router_Abstract
    implements Zend_Controller_Router_Interface
{
    public function route(Zend_Controller_Request_Abstract $dispatcher) { }
    public function assemble($userParams, $name = null, $reset = false, $encode = true) { }
    public function getFrontController() { }
    public function setFrontController(Zend_Controller_Front $controller) { }
    public function setParam($name, $value) { }
    public function setParams(array $params) { }
    public function getParam($name) { }
    public function getParams() { }
    public function clearParams($name = null) { }
}
