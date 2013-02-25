<?php

class ErrorController extends Auth_Controller_Action
{

	/**
	 * Gracefully handle errors.
	 *
	 * @access	public
	 * @return	void
	 */
    public function errorAction()
    {
        $this->disableLayout();

		// get the request object
		$request = $this->getRequest();

		// get the context
		$context = $this->_helper->getHelper('contextSwitch')->getCurrentContext();

		// handle ajax errors
		if ($context == 'json') {
			$this->view->status = 'error';
		}

		// the exception
        $errors = $this->view->errors = $this->_getParam('error_handler');
        if (!$errors) {
            $this->view->message = 'You have reached the error page.';
            return;
        }

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
				if ($context != 'json') {
					$this->getResponse()->setHttpResponseCode(404);
				}
                $priority = Zend_Log::NOTICE;
                $this->view->title = 'Uh Oh! This Page Was Not Found';
                $this->view->message = "We can't find the page you're looking for. Here is what you can do while we search for it.";
                $this->view->code = 404;
                break;
            default:
                // application error
				if ($context != 'json') {
					$this->getResponse()->setHttpResponseCode(500);
				}
                $priority = Zend_Log::CRIT;
                $this->view->title = 'Oops! Something went wrong';
                $this->view->message = "We'll try to fix it with the quickness.";
                $this->view->code = 500;
                break;
        }

		// locally log the exception message
		error_log($errors->exception->getMessage());
		error_log(print_r($errors->exception, true));

        // Log exception, if logger available
		$log = $this->getLog();
        if ($log) {

            $log->log('Code ' . $errors['exception']->getCode() . ': ' . $errors['exception']->getMessage(), $priority);
			$log->log('Line ' . $errors['exception']->getLine() . ': ' . $errors['exception']->getFile(), $priority);
			$log->log("Trace:\n " . $errors['exception']->getTraceAsString(), $priority);
            $log->log('Request Parameters: ' . print_r($request->getParams(), true), $priority);

            // send out an email
            if (!in_array(APPLICATION_ENV, array('local', 'localhost', 'development', 'testing'))) {

                $message = 'Code ' . $errors['exception']->getCode() . ': ' . $errors['exception']->getMessage() . "\n";
                $message .= 'Line ' . $errors['exception']->getLine() . ': ' . $errors['exception']->getFile() . "\n";
                $message .= "Trace:\n\n" . $errors['exception']->getTraceAsString() . "\n";
                $message .= "Request Parameters:\n\n" . print_r($request->getParams(), true) . "\n";

                $headers = 'From: do-not-reply@retargeter.com' . "\r\n" .
                            'Reply-To: do-not-reply@retargeter.com' . "\r\n" .
                            'X-Mailer: PHP/' . phpversion();

                try {
                    mail('michael@skookum.com', 'Parallon Jobs - Exception Logger', $message, $headers);
                } catch (Exception $e) {
                    // do nothing
					error_log(print_r($e, true));
                }

            }

        }

        // conditionally display exceptions for non-ajax
		if ($context != 'json') {
			if ($this->getInvokeArg('displayExceptions') == true) {
				$this->view->exception = $errors->exception;
			}

			$this->view->request = $errors->request;
		} else if (!$this->_isAjax) {

			// disable view renderer
			$this->_helper->viewRenderer->setNoRender(true);

			// limit the return data
			$ret = array(
				'status' => $this->view->status,
				'message' => $this->view->message,
				'code' => $this->view->code
			);

			die(Zend_Json::encode($ret));
		}
    }

    /**
     * Checks for a logger in the bootstrap.
     *
     * @access  public
     * @return  Logger
     */
    public function getLog()
    {
		if (!Zend_Registry::isRegistered('Logger')) {
			return false;
		}

		$Logger = Zend_Registry::get('Logger');
		return ($Logger) ? $Logger : false;
    }

}

