<?php
/**
 * Noumenal PHP Library.
 *
 * PHP classes built on top of Zend Framework. (http://framework.zend.com/)
 *
 * Bug Reports: support@noumenal.co.uk
 * Questions  : https://noumenal.fogbugz.com/default.asp?noumenal
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file noumenal-new-bsd-licence.txt.
 * It is also available through the world-wide-web at this URL:
 *
 * http://noumenal.co.uk/license/new-bsd
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@noumenal.co.uk so we can send you a copy immediately.
 *
 * @package    Noumenal
 * @author     Carlton Gibson <carlton.gibson@noumenal.co.uk>
 * @copyright  Copyright (c) 2009 Noumenal Software Ltd. (http://noumenal.co.uk/)
 * @license    http://noumenal.co.uk/license/new-bsd     New BSD License
 * @version    $Revision: 3 $ $Date: 2009-08-13 16:02:49 +0100 (Thu, 13 Aug 2009) $
 */
class My_View_Helper_FlashMessenger extends Zend_View_Helper_Abstract
{
    /**
     * @var Zend_Controller_Action_Helper_FlashMessenger
     */
    private $_flashMessenger = null;

    /**
     * Display Flash Messages.
     *
     * @param  string $key      Message level for string messages
     * @param  string $template Format string for message output
     * @return string           Flash messages formatted for output
     */
    public function flashMessenger($key = 'warning', $template='<p class="%s">%s</p>')
    {
        $flashMessenger = $this->_getFlashMessenger();

        // get messages from previous requests
        $messages = $flashMessenger->getMessages();

        // add any messages from this request
        if ($flashMessenger->hasCurrentMessages()) {
            $messages = array_merge($messages, $flashMessenger->getCurrentMessages());
            //we don't need to display them twice.
            $flashMessenger->clearCurrentMessages();
        }

        // initialise return string
        $output = '';

        // process messages
        foreach ($messages as $message) {
            if (is_array($message)) {
                list($key, $message) = each($message);
            }

            if ($key == 'error') $key = 'errormsg';
            $output .= sprintf($template, $key, $message);
        }

        return $output;
    }

    /**
     * Lazily fetches FlashMessenger Instance.
     *
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    public function _getFlashMessenger()
    {
        if (null === $this->_flashMessenger) {
            $this->_flashMessenger =
                Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
        }
        return $this->_flashMessenger;
    }
}