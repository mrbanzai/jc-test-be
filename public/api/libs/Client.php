<?php
/**
 * A client implementation for the Skookum API.
 *
 * @copyright   Skookum Digital Works 2012
 * @author      Corey Ballou
 * @link        http://coreyballou.com
 */
class Skookum_Api_Client {

    /**
     * The status code returned from the last API call.
     */
    public $status_code;

	/**
	 * Storage of default configuration settings which can be overridden with
	 * setters.
	 */
	private $_config = array();

    /**
     * Base API URL.
     */
    private $apiBaseUrl = 'http://api.jobcastle.com/api/';

	/**
	 * Basic cURL client.
	 */
	private $_client = NULL;

    /**
     * Number of API request retries.
     */
    private $_retries = 3;

	/**
	 * Stores rate limiting data from the last request.
	 */
	private $_rate_limit;

    /**
     * Current API version.
     */
    private $_version = 1.0;

    /**
     * The mapping of all status codes to some nice human readable constants.
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
	 * Constructor.
	 *
	 * @access	public
	 * @param	NULL|array	$config
	 * @param	string		$publicKey
	 * @param	string		$privateKey
	 */
	public function __construct($config = NULL, $publicKey = '', $privateKey = '')
	{
        // ensure we have cURL
        if (!function_exists('curl_init')) {
            throw new Skookum_Api_Client_Exception('The PHP cURL module is required to use the API Client. It requires (PHP 4 >= 4.0.2, PHP 5). If you are using a shared host, ask if cURL has been disabled.');
        }

        // ensure we have the proper signing functions
        if (!function_exists('hash_hmac')) {
            throw new Skookum_Api_Client_Exception('Your server does not currently support the php function hash_hmac. It requires (PHP 5 >= 5.1.2, PECL hash >= 1.1).');
        }

        // call the init method to set configuration settings
        $this->init($config, $publicKey, $privateKey);
	}

    /**
     * Initialization function to reset (and override) config variables.
     *
     * @access  public
     * @param   empty|array $config
     */
    public function init($config = NULL, $publicKey = '', $privateKey = '')
    {
        // default configuration settings of expected params
        $this->_config = array(
            'publicKey'     => $publicKey,
            'privateKey'    => $privateKey
        );

        // override default configuration settings
        if (!empty($config) && is_array($config)) {
            $this->_config = array_merge($this->_config, $config);

			// check if overriding api base url
			if (!empty($config['apiBaseUrl'])) {
				$this->apiBaseUrl = $config['apiBaseUrl'];
			}
		}

        // ensure we have a public and private key
        if (empty($this->_config['publicKey']) || empty($this->_config['privateKey'])) {
            throw new Skookum_Api_Client_Exception('You must provide a publicKey and privateKey to utilize the TimeSync API Client.');
        }
    }

    /**
     * A wrapper function around request() for GET data.
     *
     * @access  public
     * @return  mixed
     */
    public function get($path, array $params = array())
    {
        return $this->request($path, 'GET', $params);
    }

    /**
     * A wrapper function around request() for POST data.
     *
     * @access  public
     * @return  mixed
     */
    public function post($path, array $params = array())
    {
        return $this->request($path, 'POST', $params);
    }

    /**
     * A wrapper function around request() for PUT data.
     *
     * @access  public
     * @return  mixed
     */
    public function put()
    {
        return $this->request($path, 'PUT', $params);
    }

    /**
     * A wrapper function around request() for DELETE data.
     *
     * @access  public
     * @return  mixed
     */
    public function delete()
    {
        return $this->request($path, 'DELETE', $params);
    }

	/**
	 * Execute a ApiClient API request.
	 *
	 * @access  public
	 * @param 	string 		$path       The API action path
	 * @param   string      $method     Either GET or POST
	 * @param 	array|null 	$params     The API action parameters (key/val)
	 * @return 	string 		            Response of the API
	 */
	public function request($path, $method = 'POST', array $params = array())
    {

        // generate the full URL with trailing slash
        $url = $this->apiBaseUrl . trim($path, '/') . '/';

        // if we already have a valid resource
        if (!is_resource($this->_client) || get_resource_type($this->_client) != 'curl') {
            $this->_client = curl_init();
        }

		// fix possibility of unknown host and port
		if (!isset($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = 'localhost';
		}

		if (!isset($_SERVER['SERVER_PORT'])) {
			$_SERVER['SERVER_PORT'] = 80;
		}

		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = '';
		}

		// get the referrer
		$scheme 	= (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
		$referrer 	= $scheme . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];

        // get the user agent
        $user_agent = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] . ' - Client v' . $this->_version : 'Client v' . $this->_version;

        // set the cURL options
        $opts = array(
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_USERAGENT       => $user_agent,
			CURLOPT_REFERER			=> $referrer,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_HTTPHEADER 		=> array('Expect:'),
            CURLOPT_HEADER 			=> false
        );

        // prepare the parameters
        $params = $this->_prepareQueryString($params);

        // fix method
        $method = strtoupper($method);

        // handle the request method
        if ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
            $url .= (!empty($params) && is_array($params) ? '?' . http_build_query($params, NULL, '&') : '');
        } else {
            if ($method === 'POST') {
                $opts[CURLOPT_POST] = true;
            } else if ($method === 'PUT') {
                $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
            } else if ($method === 'DELETE') {
                $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            }

            // add params
            if (!empty($params) && is_array($params)) {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($params, NULL, '&');
            }
        }

        // set the url
        $opts[CURLOPT_URL] = $url;

        // set the options
        curl_setopt_array($this->_client, $opts);

        // handle retries
        $retries = (int) $this->_retries;

        // handle incremental sleep
        $sleep = 1;

        do {

            // grab the response
            $response = curl_exec($this->_client);

			// decode the response
			$response = $this->response($response);

            // get the status code
            $this->status_code = curl_getinfo($this->_client, CURLINFO_HTTP_CODE);
            if (in_array($this->status_code, array(self::STATUS_SUCCESS, self::STATUS_UNMODIFIED))) {

				// close curl
				$this->_closeCurl();

				// verify the result status code
				if (isset($response['status_code']) &&
					in_array($response['status_code'], array(self::STATUS_SUCCESS, self::STATUS_UNMODIFIED))) {
					return $response;
				}

				// return the error
				return $this->handleErrorCode($response['status_code'], $response);

			} else if (isset($response['status_code'])) {
				if (in_array($response['status_code'], array(self::STATUS_INTERNALERROR, self::STATUS_OVERLOAD))) {
					sleep($sleep);
					$sleep += $sleep;
				} else {
					return $this->handleErrorCode($response['status_code'], $response);
				}
			} else {
				return $this->handleErrorCode(self::STATUS_INTERNALERROR);
			}

        } while ($retries--);

        // return error
        return $this->handleErrorCode(self::STATUS_INTERNALERROR);
	}

    /**
     * Pass back an API response after decoding the JSON string.
     *
     * @access  public
     * @param   string      $response   JSON response string
     * @return  object|bool
     */
    public function response($response)
    {
        try {

			$response = json_decode($response, TRUE);
			if (!isset($response['status_code'])) {
				$response['status_code'] = self::STATUS_INTERNALERROR;
			}

			if (!isset($response['data'])) {
				$response['data'] = array();
			}

			if (isset($response['rate_limit'])) {
                $this->updateRateLimit($response['rate_limit']);
            }

            return $response;

        } catch (Exception $e) {
			error_log($e->getMessage());
            return FALSE;
        }
    }

	/**
	 * Handle errors in a user friendly manner.
	 *
	 * @access	public
	 * @param	mixed	$status_code
	 * @param	array	$result
	 * @return	array
	 */
	public function handleErrorCode($status_code = NULL, $result = array())
	{
		// close curl
		$this->_closeCurl();

		// override the header status code if necessary
		if ($status_code) {
			$this->status_code = $status_code;
		}

		// generic response array
		$response = array_merge(
			$result,
			array(
				'status_code' => $this->status_code,
				'data' => array()
			)
		);

		// prettify the error
		switch ($this->status_code) {
			case self::STATUS_SUCCESS:
			case self::STATUS_UNMODIFIED:
				break;
			case self::STATUS_BADREQUEST:
				$response['error'] = 'The request could not be understood by the server due to missing or malformed parameters.';
				break;
			case self::STATUS_UNAUTHORIZED:
				$response['error'] = 'Unauthorized access. The request was not signed properly or your public/private key pair is incorrect for your domain.';
				break;
			case self::STATUS_FORBIDDEN:
				$response['error'] = 'The server understood the request, but is refusing to fulfill it. You may not have access to the API call.';
				break;
			case self::STATUS_NOTFOUND:
				$response['error'] = 'No API method exists matching the URL specified.';
				break;
			case self::STATUS_INVALID:
				$response['error'] = 'The request method was not valid.';
				break;
			case self::STATUS_CHILL:
				$response['error'] = 'You have exceeded your rate limit.';
				break;
			case self::STATUS_INTERNALERROR:
				$response['error'] = 'The server encountered an unexpected condition which prevented it from fulfilling the request.';
				break;
			case self::STATUS_OVERLOAD:
				$response['error'] = 'The server is currently unable to handle the request due to a temporary overloading or maintenance of the server.';
				break;
		}

		return $response;
	}

	/**
	 * Wrapper to return the last status code received from the server.
	 *
	 * @access	public
	 */
	public function lastStatusCode()
    {
        return $this->status_code;
    }

	/**
	 * Closes the cURL client.
	 *
	 * @access	private
	 */
	private function _closeCurl()
	{
		if ($this->_client != NULL) {
			// close the client
			curl_close($this->_client);
			// reset the client
			$this->_client = NULL;
		}
	}

	/**
	 * Updates the rate limit variables. The rate limit variable in the response
	 * contains the following: [hourly_limit, remaining, next_update]
	 *
	 * @access	private
	 */
	private function _updateRateLimit($rate_limit)
	{
		$this->_rate_limit = $rate_limit;
	}

    /**
     * Prepares the query string parameters for sending. This is independent
     * of the request method. Ensures the public key is passed as well as
     * the content verification signature.
     *
     * @access  private
     * @param   array   $params
     */
    private function _prepareQueryString($params)
    {
        // allow overridding public and private keys in parameters
        $publicKey = !empty($params['publicKey']) ? $params['publicKey'] : $this->_config['publicKey'];
        $privateKey = !empty($params['privateKey']) ? $params['privateKey'] : $this->_config['privateKey'];
        unset($params['publicKey'], $params['privateKey']);

		// convert all parameters to UTF-8
		$params = $this->_toUtf8($params);

        // generate data string
        $data = !empty($params) ? json_encode($params) : '';

        // generate a content verification signature
        $sig = base64_encode(hash_hmac('sha1', $data, $privateKey, FALSE));

		// return the prepared data
		return array('data' => $data, 'sig' => $sig, 'publicKey'=> $publicKey);
    }

	/**
	 * Attempts to convert any non-utf8 string to UTF-8.
	 *
	 * @access	public
	 * @param	string|array	$str
	 * @return	string|array
	 */
	private function _toUtf8($str)
	{
		$out = array();
        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $out[$this->_toUtf8($k)] = $this->_toUtf8($v);
            }
        } else if (is_string($str)) {
            if (mb_detect_encoding($str) != 'UTF-8')
				return utf8_encode($str);
            return $str;
        } else {
            return $str;
        }
        return $out;
	}

}
