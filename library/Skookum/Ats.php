<?php
/**
 * Base ATS class.
 */
class Skookum_Ats {

    /**
     * The QueryPath object.
     */
    protected $_qp;

    /**
     * The cURL handle.
     */
    protected $_ch;

	/**
	 * Function which verifies a page exists prior to returning
	 * the HTML from the page.
	 *
	 * @access 	public
	 * @param	string	$url
	 * @param   mixed   $referrer
	 * @return	mixed
	 */
	public function request($url, $referrer = null, $timeout = 30, $retries = 0)
	{
        if (!$this->_ch) {
            $this->_ch = curl_init();
        }

		curl_setopt($this->_ch, CURLOPT_URL, $url);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($this->_ch, CURLOPT_TIMEOUT, $timeout);

        // handle cookies
        if (!empty($referrer)) {
            $cookiename = 'ats_' . md5(parse_url($referrer, PHP_URL_HOST)) . '.txt';
        } else {
            $cookiename = 'ats_' . md5(parse_url($url, PHP_URL_HOST)) . '.txt';
        }

        curl_setopt ($this->_ch, CURLOPT_COOKIEJAR, '/tmp/' . $cookiename);
        curl_setopt ($this->_ch, CURLOPT_COOKIEFILE, '/tmp/' . $cookiename);

        // handle referrer urls
        if (!empty($referrer)) {
            curl_setopt($this->_ch, CURLOPT_REFERER, $referrer);
        }

		// handle HTTPS links if necessary
		if (strpos($url, 'https') !== false) {
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST,  1);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
		} else {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST,  0);
        }

		// get the data response
		$response = curl_exec($this->_ch);

		// check for error up front
		if ($response === false) {
			// handle retries
			if ($retries > 3) {
				// add 15 seconds to timeout
				$timeout += 15;
				// incremnt retries
				$retries++;
				// try try again...
				return $this->request($url, $referrer, $timeout, $retries);
			}
		}

		// get the response code
		$statusCode = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);

		// return data if status code matches
		return ($statusCode >= 200 && $statusCode < 400) ? $response : false;
	}

    /**
     * Retrieve specific QueryPath options based on the content type.
     *
     * @access  public
     * @param   string  $type
     * @return  array
     */
    public function getOptionsByType($type)
    {
        $type = strtolower($type);
        $return = array();
        if ($type == 'xml') {
            return array('QueryPath_class' => 'QueryPath', 'use_parser' => 'xml');
        } else if ($type == 'html') {
            return array(
                'ignore_parser_warnings' => TRUE,
                'convert_to_encoding' => 'UTF-8',
                'convert_from_encoding' => 'auto',
                'replace_entities' => FALSE,
                'use_parser' => 'html',
                'strip_low_ascii' => FALSE
            );
        }
        return array();
    }

    /**
     * Cleans HTML data.
     *
     * @access  public
     * @param   string  $data
     * @return  string
     */
    public function cleanse($data)
    {
        $data = html_entity_decode($data);
        $data = preg_replace("/[\s]+/s", " ", $data);
        $data = preg_replace("/[\t]+/s", " ", $data);
        $data = preg_replace("/[\r\n]+/s", "\n", $data);
        $data = preg_replace( '/[\x7f-\xff]/', '', $data);
        return trim($data);
    }

	/**
	 * Attempt to fix states by converting them to their two digit counterpart.
	 *
	 * @access	public
	 * @param	string	$state
	 * @return	string
	 */
	public function fixStates($state)
	{
		if (empty($state)) return null;
		else if (strlen($state) == 2) return $state;

        $states = array(
            'ALABAMA'					=> 'AL',
            'ALASKA'					=> 'AK',
            'ARIZONA'					=> 'AZ',
            'ARKANSAS'					=> 'AR',
            'CALIFORNIA'				=> 'CA',
            'COLORADO'					=> 'CO',
            'CONNECTICUT'				=> 'CT',
            'DELAWARE'					=> 'DE',
            'DISTRICT OF COLUMBIA'		=> 'DC',
            'FLORIDA'					=> 'FL',
            'GEORGIA'					=> 'GA',
            'HAWAII'					=> 'HI',
            'IDAHO'						=> 'ID',
            'ILLINOIS'					=> 'IL',
            'INDIANA'					=> 'IN',
            'IOWA'						=> 'IA',
            'KANSAS'					=> 'KS',
            'KENTUCKY'					=> 'KY',
            'LOUISIANA'					=> 'LA',
            'MAINE'						=> 'ME',
            'MARYLAND'					=> 'MD',
            'MASSACHUSETTS'				=> 'MA',
            'MICHIGAN'					=> 'MI',
            'MINNESOTA'					=> 'MN',
            'MISSISSIPPI'				=> 'MS',
            'MISSOURI'					=> 'MO',
            'MONTANA'					=> 'MT',
            'NEBRASKA'					=> 'NE',
            'NEVADA'					=> 'NV',
            'NEW HAMPSHIRE'				=> 'NH',
            'NEW JERSEY'				=> 'NJ',
            'NEW MEXICO'				=> 'NM',
            'NEW YORK'					=> 'NY',
            'NORTH CAROLINA'			=> 'NC',
            'NORTH DAKOTA'				=> 'ND',
            'OHIO'						=> 'OH',
            'OKLAHOMA'					=> 'OK',
            'OREGON'					=> 'OR',
            'PENNSYLVANIA'				=> 'PA',
            'RHODE ISLAND'				=> 'RI',
            'SOUTH CAROLINA'			=> 'SC',
            'SOUTH DAKOTA'				=> 'SD',
            'TENNESSEE'					=> 'TN',
            'TEXAS'						=> 'TX',
            'UTAH'						=> 'UT',
            'VERMONT'					=> 'VT',
            'VIRGINIA'					=> 'VA',
            'WASHINGTON'				=> 'WA',
            'WEST VIRGINIA'				=> 'WV',
            'WISCONSIN'					=> 'WI',
            'WYOMING'					=> 'WY',
            'ONTARIO'					=> 'ON',
            'QUEBEC'					=> 'QC',
            'NOVA SCOTIA'				=> 'NS',
            'NEW BRUNSWICK'				=> 'NB',
            'MANITOBA'					=> 'MB',
            'BRITISH COLUMBIA'			=> 'BC',
            'PRINCE EDWARD ISLAND'		=> 'PE',
            'SASKATCHEWAN'				=> 'SK',
            'ALBERTA'					=> 'AB',
            'NEWFOUNDLAND'				=> 'NF',
            'NORTHWEST TERRITORIES'		=> 'NT',
            'YUKON TERRITORY'			=> 'YT',
            'NUNAVUT'					=> 'NU'
        );

		// check against mapping
		if (isset($states[$state])) {
			return $states[$state];
		}

		return $state;
	}

    /**
     * Close cURL on destruction.
     *
     * @access  public
     */
    public function __destruct()
    {
		// close connection
        if ($this->_ch) {
            curl_close($this->_ch);
        }
    }

}
