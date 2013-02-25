<?php

class Skookum_Controller_Plugin_Mobile extends Zend_Controller_Plugin_Abstract
{

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $frontController = Zend_Controller_Front::getInstance();
        $bootstrap = $frontController->getParam('bootstrap');

        if (!$bootstrap->hasResource('useragent')) {
            throw new Zend_Controller_Exception('The mobile plugin can only be loaded when the UserAgent resource is bootstrapped');
        }

        $userAgent = $bootstrap->getResource('useragent');
        // Load device settings, required to perform $userAgent->getBrowserType()
        $userAgent->getDevice();

        if ($userAgent->getBrowserType() === 'mobile') {
            if ($frontController->getParam('mobileLayout') == '1') {
                $suffix = $bootstrap->getResource('layout')->getViewSuffix();
                $bootstrap->getResource('layout')->setViewSuffix('mobile.' . $suffix);
            }

            if ($frontController->getParam('mobileViews') == '1') {
                Zend_Controller_Action_HelperBroker::getStaticHelper('MobileContext')->enable();
            }
        }
    }

}