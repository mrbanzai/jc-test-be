<?php
class Skookum_Caterpillar_Security extends Skookum_Caterpillar_Crawler {

	protected $forms	= array();
	protected $vectors 	= array();

	/**
	 * Crawl and index the website specified in the config.
	 */
    public function crawl()
    {
        parent::crawl();

		// remove old entries
		$this->truncateTables();

        // test found forms for security holes
        $this->run_scanner();
    }

	/**
	 * When a URL is found, add it to the index.
	 */
    protected function onUrlFound($url, $data)
    {
		// get the returned id of the URL in the index
        $id = $this->addUrlToIndex($url);

		// determine if any forms exist on the page and their methods
		$this->scanStringForForm($id, $url, $data);
    }

	/**
	 * Remove any old entries from the security crawling tables.
	 */
	protected function truncateTables()
	{
		mysql_query('TRUNCATE TABLE crawl_security_form', $this->db);
		mysql_query('TRUNCATE TABLE crawl_security_form_elements', $this->db);
		mysql_query('TRUNCATE TABLE crawl_security_results', $this->db);
	}

	/**
	 * Scans an HTML string for $_REQUEST parsing.  If any such
	 * variables exist, store all variables with their associated
	 * method in the database for future testing.
	 */
	protected function scanStringForForm($page_id, $url, $data)
	{

		// create storage array for field names
		$fieldNames = array();

		// check page for a <form> element
		$result = array();
		if (preg_match_all('/\<form(.*?)\>(.*?)\<\/form\>/is', $data, $result)) {

			// if form(s) found
			if (!empty($result[1])) {

				// get the number of forms
				$formCount = count($result[1]);

				// iterate over forms
				for ($i = 0; $i < $formCount; ++$i) {

					// get the form method
					$method = $this->getFormMethod($result[1][$i]);

					// get the form action
					$action = $this->getFormAction($result[1][$i], $url);
					if (strpos('http', $action) === false) {
						$action = 'http://' . $this->startUrl . '/' . ltrim($action, '/');
					}

					// get form element names from inner content
					$this->getFormFields($result[2][$i], $fieldNames);

					// create form entry
					$form_id = $this->createFormEntry($page_id, $method, $action);

					// create association to form elements
					$this->createFormElementAssociation($form_id, $fieldNames);

				}

			}

		}

	}

	/**
	 * Retrieve the form method from the string.
	 */
	protected function getFormMethod($formTag)
	{
		$methodResult = array();
		if (preg_match('/method="(.*?)"/is', $formTag, $methodResult)) {
			return (!empty($methodResult[1])) ? strtoupper($methodResult[1]) : 'POST';
		} else {
			return 'GET';
		}
	}

	/**
	 * Retrieve the form action from the result.
	 */
	protected function getFormAction($formTag, $url)
	{
		$actionResult = array();
		if (preg_match('/action="(.*?)"/is', $formTag, $actionResult)) {
			return (!empty($actionResult[1])) ? strtolower($actionResult[1]) : $url;
		} else {
			return $url;
		}
	}

	/**
	 * Retrieve any form fields contained within the form.
	 */
	protected function getFormFields($formContents, &$fieldNames)
	{
		$nameResults = array();
		if (preg_match_all('/name="(.*?)"/is', $formContents, $nameResults)) {
			if (!empty($nameResults[1])) {
				foreach ($nameResults[1] as $name) {
					$fieldNames[] = $name;
				}
			}
		}
	}

	/**
	 * Creates an entry in the db for the found form.
	 */
	protected function createFormEntry($page_id, $method, $action)
	{
		// create entry for form
		$sql = sprintf('INSERT INTO crawl_security_form SET
						page_id = %d,
						method = "%s",
						action = "%s"',
						(int) $page_id,
						mysql_real_escape_string($method),
						mysql_real_escape_string($action));

		mysql_query($sql, $this->db);

		// store form data
		$form_id = mysql_insert_id();
		$this->forms[$form_id] = array('page_id' => $page_id, 'method' => $method, 'action' => $action, 'elements' => array());

		// return the form id
		return $form_id;
	}

	/**
	 * Creates a form element association to the found form.
	 */
	protected function createFormElementAssociation($form_id, &$fieldNames)
	{
		foreach ($fieldNames as $name) {
			$sql = sprintf('INSERT INTO crawl_security_form_elements SET
							form_id = %d,
							name = "%s"',
							(int) $form_id,
							mysql_real_escape_string($name));

			mysql_query($sql, $this->db);

			// insert element into form array
			$this->forms[$form_id]['elements'][] = $name;
		}

		// reset fieldNames array
		$fieldNames = array();
	}

	/**
	 * Runs the security scanner against each found form on the
	 * given webpage.  The scanner has a multitude of attack vectors
	 * for both SQL injection and XSS.
	 */
	public function run_scanner()
	{
		// get all attack vectors
		$sql = sprintf('SELECT id, name, type, code FROM crawl_security_vectors');
		$res = mysql_query($sql, $this->db);
		if (mysql_num_rows($res) > 0) {
			while ($r = mysql_fetch_assoc($res)) {
				$this->vectors[] = $r;
			}
		}

		if (empty($this->forms)) {
			$this->forms = $this->getFormData();
		}

		// try all attack vectors on each form
		foreach ($this->forms as $form_id => $form) {
			foreach ($this->vectors as $vector) {
				$this->send_attack_vector($form_id, $form, $vector);
			}
		}
	}

	/**
	 * Makes a page request with the attack vector.
	 */
	protected function send_attack_vector($form_id, $form, $vector)
	{

		// create a URI string based on form elements
		$uri_string = $this->createUriString($form['elements'], $vector['code']);

		$this->ch = curl_init();

		if ($form['method'] !== 'POST') {
			curl_setopt($this->ch, CURLOPT_URL, $form['action'] . '?' . $uri_string);
		} else {
			curl_setopt($this->ch, CURLOPT_URL, $form['action']);
		}
		curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 1);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 20);

		if ($form['method'] === 'POST') {
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $uri_string);
		}

		// handle HTTPS links if necessary
		if (strpos($form['action'], 'https') !== false) {
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST,  1);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		// get the data response
		$response = curl_exec($this->ch);

		// close connection
		curl_close($this->ch);

		// create result entry for the form
		$sql = sprintf('INSERT INTO crawl_security_results SET
						form_id = %d,
						vector_id = %d,
						results = "%s"',
						(int) $form_id,
						(int) $vector['id'],
						mysql_real_escape_string($response));

		mysql_query($sql, $this->db);
	}

	/**
	 * Creates a URI string based on the form element names and their accompanying vector code.
	 */
	protected function createUriString($formElements, $vectorCode)
	{
		$uri_string = '';
		foreach ($formElements as $element) {
			$uri_string .= $element . '=' . urlencode($vectorCode) . '&';
		}
		return rtrim($uri_string, '?');
	}

	/**
	 * Get data for the form.
	 */
	protected function getFormData()
	{
		$array = array();
		$sql = sprintf('SELECT id, page_id, method, action FROM crawl_security_form');
		$res = mysql_query($sql, $this->db);
		if (mysql_num_rows($res) > 0) {
			while ($row = mysql_fetch_assoc($res)) {
				$elements = array();
				$sql = sprintf('SELECT id, name FROM crawl_security_form_elements WHERE form_id = %d', (int) $row['id']);
				$res2 = mysql_query($sql, $this->db);
				if (mysql_num_rows($res2) > 0) {
					while ($row2 = mysql_fetch_assoc($res2)) {
						$elements[] = $row2['name'];
					}
				}
				$array[ $row['id'] ] = array('page_id' => $row['page_id'],
											 'method' => $row['method'],
											 'action' => $row['action'],
											 'elements' => $elements);
			}
		}
		return $array;
	}

}
?>
