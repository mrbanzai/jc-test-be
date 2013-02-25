<?php

/**
 * The base RESTful (term used loosely, mind you) class is intended to handle
 * low level API processing like public key authentication.
 */
class Skookum_Api_Server extends Zend_Controller_Action {

    /**
     * The mapping of all error codes to some nice human readable constants.
     */
    const STATUS_SUCCESS = 200;
    const STATUS_UNMODIFIED = 304;
    const STATUS_BADREQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOTFOUND = 404;
    const STATUS_INVALID = 405;
    const STATUS_CHILL = 420;
    const STATUS_INTERNALERROR = 500;
    const STATUS_OVERLOAD = 502;

	/**
	 * Global config.
	 *
	 * @var	array
	 */
	protected $_global = array();

    /**
     * The request object.
     *
     * @var object
     */
    protected $_request;

	/**
	 * The response object.
	 *
	 * @var	object
	 */
	protected $_response;

    /**
     * The request method used.
     *
     * @var string
     */
    protected $_requestMethod;

	/**
	 * Whether the request has already been dispatched.
	 *
	 * @var bool
	 */
	protected $_dispatched = false;

	/**
	 * Is the request coming via AJAX.
	 *
	 * @var	bool
	 */
	protected $_isAjax = false;

	/**
	 * The JSONP callback for the request.
	 *
	 * @var	mixed
	 */
	protected $_jsonpCallback;

    /**
     * Contains the initial requested action.
     *
     * @var string
     */
    protected $_action;

    /**
     * Override the mapping to currently only allow GET and POST by default.
     * This should be overridden on a per-class basis. The value supports
     * strings as well as an array of strings.
     *
     * @var array
     */
	protected $_action_map = array();

	/**
	 * Specify validation parameters for a particular request action. The sub-array
	 * of parameters is stored as PARAM => VALIDATOR where the validator can
	 * also ensure the data is of the proper type.
	 *
	 * @var array
	 */
	protected $validators = array();

	/**
	 * Holds any fields that are missing or incorrect.
	 *
	 * @var array
	 */
	protected $errors = array();

    /**
     * The server model.
     *
     * @var model
     */
    protected $rest;

    /**
     * Contains the status code when needed for the generic error action.
     *
     * @var int
     */
    protected $status_code;

    /**
     * Stores the request data (POST or GET).
     *
     * @var array
     */
    protected $data;

	/**
	 * Stores the current user.
	 *
	 * @var	int
	 */
	protected $user;

    /**
     * Stores the decoded params from the request data.
     *
     * @var array
     */
    protected $params = array();

    /**
     * Key storage for the user.
     *
     * @var array
     */
    protected $keys = array();

    /**
     * Trimmed referrer.
     *
     * @var string
     */
    protected $referrer;

	/**
	 * Checks the requested method against the available methods. If the method
	 * is supported, sets the request action from the map. If not supported,
	 * the "invalid" action will be called.
	 *
	 * @access  public
	 */
    public function init()
    {
        parent::init();

        // disable view rendering since we aren't dealing with HTML
        $this->disableRender();

        // load the appropriate API model
        $this->rest = new Skookum_Api_Server_Model();

		// ensure we have some globals loaded
		$this->_global = Zend_Registry::get('global');
	}

    /**
     * Handles parsing the request and forwarding to the appropriate
     * controller and action.
     *
     * @access  public
     * @return  void
     */
    public function preDispatch()
    {
		// keep a copy of the request and response
		$this->_request = $this->getRequest();
		$this->_response = $this->getResponse();
		$this->_controller = $this->_request->controller;
		$this->_action = $this->_request->action;

        // forward to controller and action
		if (!$this->_request->getParam('force_dispatch')) {

			// set the referrer
			$this->referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;

			// check for ajax
			$this->_isAjax = $this->_request->isXmlHttpRequest();

			// determine request method
			if ($this->_request->isGet()) {
				$this->_requestMethod = 'GET';
				$this->data = $this->_request->getQuery();
			} else if ($this->_request->isPost()) {
				$this->_requestMethod = 'POST';
				$this->data = $this->_request->getPost();
			} else if ($this->_request->isPut()) {
				$this->_requestMethod = 'PUT';
				parse_str($this->_request->getRawBody(), $this->data);
			} else if ($this->_request->isDelete()) {
				$this->_requestMethod = 'DELETE';
				parse_str($this->_request->getRawBody(), $this->data);
			}

			// check for JSONP
			$this->_jsonpCallback = !empty($this->data['callback']) ? $this->data['callback'] : NULL;

			// ensure the signature and action are valid
			if ($this->verifyAuthorization()) {
				$this->verifyAction();
			}

			// if we had a problem
			if ($this->_action == 'invalid' || $this->_action == 'error') {
				return $this->_request
					->setActionName($this->_action)
					->setParam('force_dispatch', true)
					->setParam('errors', $this->errors)
					->setParam('status_code', $this->status_code)
					->setParam('_isAjax', $this->_isAjax)
					->setParam('_jsonpCallback', $this->_jsonpCallback)
					->setDispatched(false);
			} else {
				// assume we're hitting a valid endpoint
				$this->status_code = Skookum_Api_Server::STATUS_SUCCESS;
			}

		} else {
			// handle the error scenarios
			$this->errors = $this->_request->getParam('errors', array());
			$this->status_code = $this->_request->getParam('status_code', 403);
			$this->_isAjax = $this->_request->getParam('_isAjax', false);
			$this->_jsonpCallback = $this->_request->getParam('_jsonpCallback', null);
		}
    }

	/**
	 * Sends a 405 "Method Not Allowed" response and a list of allowed actions.
	 *
	 * @action  public
	 */
	public function invalidAction()
	{
        $response = array(
			'status_code'   => self::STATUS_INVALID,
			'errors'        => $this->errors,
			'data'          => array()
		);

		return $this->sendFile($response, 'invalid.json');
	}

    /**
     * Default action for an error.
     *
     * @action  public
     */
    public function errorAction()
    {
        $response = array(
			'status_code'   => !empty($this->status_code) ? $this->status_code : 403,
			'errors'        => $this->errors,
			'data'          => array()
		);

		return $this->sendFile($response, 'error.json');
    }

    /**
     * Handles verification of the request signature to ensure that the request
     * is valid.
     *
     * @access  public
     */
    protected function verifyAuthorization()
    {
        // verify the request method was okay
        if (!isset($this->_action_map[$this->_requestMethod])) {
			$this->errors[] = 'The endpoint you have requested does not appear to exist.';
            return $this->setErrorCode(self::STATUS_INVALID);
        }

        // ensure they passed a publicKey and signature
        if (!isset($this->data['publicKey']) || !isset($this->data['sig'])) {
			$this->errors[] = 'Your request is not formatted correctly (i.e. it may be missing keys or a signature).';
            return $this->setErrorCode(self::STATUS_BADREQUEST);
        }

		// check if we need special referrer handling for ajax
		if ($this->_isAjax && empty($this->referrer)) {
			$this->referrer = !empty($this->data['data']['referrer']) ? $this->data['data']['referrer'] : NULL;
		}

        // attempt to determine the referrer's domain
        if (empty($this->referrer)) {
			$this->errors[] = 'Your request is missing a referrer.';
            return $this->setErrorCode(self::STATUS_UNAUTHORIZED);
        }

        // parse the referrer into segments
        $this->referrer = parse_url($this->referrer, PHP_URL_HOST);
        if ($this->referrer === FALSE) {
			$this->errors[] = 'Your domain does not have permission to make a request.';
            return $this->setErrorCode(self::STATUS_UNAUTHORIZED);
        }

        // be generous, remove www
        if (strpos($this->referrer, 'www.') === 0) {
            $this->referrer = str_replace('www.', '', $this->referrer);
        }

        try {

            // ensure we've got a domain match for the public key
            $result  = $this->rest->validatePublicKey($this->data['publicKey'], $this->referrer);
            if ($result === FALSE) {
                $this->errors[] = 'Your public/private key pairings could not be verified.';
                return $this->setErrorCode(self::STATUS_UNAUTHORIZED);
            }

			// get some other necessary values
			$privateKey = $result['private'];
			$this->user = $result['user_id'];

            // get the request data (already JSON encoded)
            $request = (string) $this->data['data'];

            // compute the signature of the data and compare
            $request_signature = base64_encode(hash_hmac('sha1', $request, $privateKey, FALSE));
            if ($request_signature !== $this->data['sig']) {
                $this->errors[] = 'Your request signature does not match. Potential request forgery.';
                return $this->setErrorCode(self::STATUS_UNAUTHORIZED);
            }

            // signature verification successful
            if (get_magic_quotes_gpc()) {
                $request = stripslashes($request);
            }

            // store the necessary request params
            $this->params = json_decode($request, TRUE);

			// store the keys
            $this->keys = array(
				'public' => $this->data['publicKey'],
				'private' => $privateKey
			);

            // garbage collect
            $this->data = NULL;

            // return
            return TRUE;

        } catch (Exception $e) {
            // skip to blank return
			error_log(print_r($e->getMessage()));
        }

        // something went wrong...
        return $this->setErrorCode(self::STATUS_INTERNALERROR);
    }

	/**
	 * Verify the given action is valid for the controller in question.
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function verifyAction()
	{
		// check for different cases of the action
		if (is_string($this->_action_map[$this->_requestMethod])) {
			if ($this->_action == $this->_action_map[$this->_requestMethod]) {
				$this->_action = $this->_action;
			} else {
				$this->_action = 'invalid';
			}
		} else {
			// set the proper action case
			if (in_array($this->_action, $this->_action_map[$this->_requestMethod])) {
				$this->_action = $this->_action;
			} else {
				$this->_action = 'invalid';
			}
		}
	}

	/**
	 * Ensures the controller action passes all validation. Optionally
	 * supports the following rules:
	 *
	 * alpha, alphanum, int, float, bool, email, url, ip
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function hasValidParameters()
	{
		if (empty($this->validators[$this->_action])) {
			return TRUE;
		}

		foreach ($this->validators[$this->_action] as $field => $rules) {
			$val = isset($this->params[$field]) ? $this->params[$field] : null;
			if (!empty($rules)) {
				if (is_string($rules)) {
					if ($this->isValidRule($val, $rules) === FALSE) {
						$this->errors[] = 'The ' . htmlentities($field) . ' parameter could not be validated against the ' . $rules . ' rule.';
						return FALSE;
					}
				} else if (is_array($rules)) {
					foreach ($rules as $k => $rule) {
						// check for possibility of keys being the rule (with params)
						if (!is_numeric($k)) {
							if ($this->isValidRule($val, $k, $rule) === FALSE) {
								$this->errors[] = 'The ' . htmlentities($field) . ' parameter could not be validated against the ' . $rule . ' rule.';
								return FALSE;
							}
						} else {
							if ($this->isValidRule($val, $rule) === FALSE) {
								$this->errors[] = 'The ' . htmlentities($field) . ' parameter could not be validated against the ' . $rule . ' rule.';
								return FALSE;
							}
						}
					}
				}
			}
		}

		return TRUE;
	}

	/**
	 * Tests whether the rule is valid or not based on the given value.
	 *
	 * @access	protected
	 * @param	string		$val
	 * @param	string		$rule
	 * @param	mixed		$params
	 * @return	bool
	 */
	protected function isValidRule($val, $rule, $params = null)
	{
		switch ($rule) {
			case 'required':
				return $val !== null && strlen($val) > 0;
				break;
			case 'alpha':
				return (preg_match("/[a-zA-Z\s_-]/i", $val) > 0) ? TRUE : FALSE;
				break;
			case 'alnum':
				return (preg_match("/[a-zA-Z0-9\s_-]/i", $val) > 0) ? TRUE : FALSE;
				break;
			case 'int':
				return filter_var($val, FILTER_VALIDATE_INT);
				break;
			case 'float':
				return filter_var($val, FILTER_VALIDATE_FLOAT);
				break;
			case 'bool':
				return filter_var($val, FILTER_VALIDATE_BOOLEAN);
				break;
			case 'numeric':
				return is_numeric($val);
				break;
			case 'minlength':
				return strlen($val) >= $params;
				break;
			case 'maxlength':
				return strlen($val) <= $params;
				break;
			case 'matches':
				return in_array($val, (array) $params);
				break;
			case 'email':
				return filter_var($val, FILTER_VALIDATE_EMAIL);
				break;
			case 'url':
				return filter_var($val, FILTER_VALIDATE_URL);
				break;
			case 'ip':
				return filter_var($val, FILTER_VALIDATE_IP);
				break;
			default:
				if (is_callable($rule)) {
					return call_user_func($rule, $val, $params);
				}
				break;
		}
		return TRUE;
	}

    /**
     * Sets an error code to be handled by the default error handler.
     *
     * @access  protected
     * @return  bool
     */
    protected function setErrorCode($error_code)
    {
        $this->status_code = $error_code;
        $this->_action = 'error';
        return FALSE;
    }

	/**
	 * After response data has been set, calling this function will force
	 * a file download.
	 *
	 * @access	public
	 * @param   string  $filepath
	 * @param   string  $filename
	 * @param   bool    $inline
	 */
	protected function sendFile($filepath, $filename = NULL, $inline = FALSE)
	{
		// set the additional download headers
		$this->setDownloadHeaders();        
        
		// if we have an array, convert to JSON
		if (is_array($filepath)) {
			if (!empty($this->errors) && !isset($filepath['errors'])) {
				$filepath['errors'] = $this->errors;
			}
			$filepath = json_encode($filepath);
		}

		// check for JSONP
		if (!empty($this->_jsonpCallback)) {
			// validate callback
			if ($this->isValidCallback($this->_jsonpCallback)) {
				$filepath = $this->_jsonpCallback . '(' . $filepath . ');';
			} else {
				unset($this->params['callback']);
				$this->errors[] = 'The JSONP callback parameter does not appear to be valid.';
				return $this->action_invalid();
			}
		}

		// generate the filename, if necessary
		if (empty($filename)) {
            $filename = preg_replace('//', $this->_controller . '_' . $this->_action) . '.json';
        }

        // handle inline
        if ($inline) {
            $options = array('disposition' => 'inline');
        } else {
            $options = array('disposition' => 'attachment');
        }

        // send the file
        return $this->_helper->sendFile->sendData(
            $filepath,
            'application/json',
            $filename,
            $options
        );
	}

	/**
	 * Sets additional headers for a JSON file download.
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function setDownloadHeaders()
	{
		$this->_response
			->setHeader('Content-Transfer-Encoding', 'binary')
			->setHeader('Accept-Ranges', 'bytes')
			->setHeader('Cache-control', 'private')
			->setHeader('Pragma', 'private')
			->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
	}

    /**
     * disable normal view rendering
     *
     * @access public
     * @return void
     */
    public function disableRender()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

	/**
	 * Ensures the JSONP callback is valid.
	 *
	 * @access	public
	 * @param	string	$callback
	 * @return	bool
	 */
	protected function isValidCallback($callback)
	{
		$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
		$reserved_words = array(
			'break', 'do', 'instanceof', 'typeof', 'case',
			'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue',
			'for', 'switch', 'while', 'debugger', 'function', 'this', 'with',
			'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum',
			'extends', 'super', 'const', 'export', 'import', 'implements', 'let',
			'private', 'public', 'yield', 'interface', 'package', 'protected',
			'static', 'null', 'true', 'false'
		);

		return preg_match($identifier_syntax, $callback) && !in_array(mb_strtolower($callback, 'UTF-8'), $reserved_words);
	}

}
