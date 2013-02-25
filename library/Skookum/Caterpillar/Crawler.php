<?php
class Skookum_Caterpillar_Crawler extends Skookum_Model
{

	/**
	 * The starting url of the crawler. This is assumed to be the full
	 * path to a website or file.
	 *
	 * @var	string
	 */
	protected $startUrl = null;

	/**
	 * The particular client we're generating the sitemap for.
	 *
	 * @var	int
	 */
	protected $client_id = null;

	/**
	 * Determines the base scheme for the crawled website.
	 *
	 * @var	string
	 */
	protected $baseScheme = null;

	/**
	 * Determines the base url for the crawled website.
	 *
	 * @var	string
	 */
	protected $baseUrl = null;

	/**
	 * An array of all ignored file extensions.
	 *
	 * @var	array
	 */
	protected $_ignoredExtensions = array('js', 'css', 'bak', 'jpg', 'jpeg', 'png', 'gif', 'swf', 'bmp', 'xml');

	/**
	 * An array of all ignored files and directories.
	 *
	 * @var	array
	 */
	protected $_ignoredFiles = array('/public/');

	/**
	 * The google sitemap XML change frequency for your files.
	 *
	 * @var	string
	 */
	protected $changeFrequency = 'daily';

	/**
	 * Stores the cURL handler once initiated.
	 *
	 * @var
	 */
	protected $ch = null;

	/**
	 * Allows limiting the depth of your search into subdirectories.
	 *
	 * @var	int
	 */
	protected $maxDepth	= 10;

	/**
	 * Database config.
	 *
	 * @var	array
	 */
	protected $config = array();

	/**
	 * Stores a mysql connection resource.
	 *
	 * @var resource
	 */
	protected $db = null;

	/**
	 * Grabs the starting crawler URL from the config based
	 * on the current environment.
	 *
	 * @access	public
	 * @param	string	$startUrl
	 */
	public function __construct($startUrl = null, $client_id = null)
	{
		parent::__construct();

		// override memory usage
		@ini_set('max_execution_time', 0);
		@ini_set('memory_limit','512M');

		// prevent mysql timeout
		@ini_set('mysql.connect_timeout', 3600);
		@ini_set('default_socket_timeout', 3600);

		// get the starting crawler url
		$this->startUrl = $startUrl;

		// set the client
		$this->client_id = $client_id;

		// validate the url
		if (!filter_var($this->startUrl, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
			throw new Exception('Your starting URL does not appear to be valid. Please ensure it starts with http://');
		}

		// determine the base url
		$parts = parse_url($this->startUrl);
		$this->baseScheme = $parts['scheme'];
		$this->baseUrl = $parts['scheme'] . '://' . $parts['host'];

		error_log('Starting crawl url: ' . $this->startUrl);
		error_log('Base crawl url: ' . $this->baseUrl);

		// retrieve the database connection variables
		$this->config = $this->_db->getConfig();

		// connect to mysql
		$this->dbConnect();

		// remove other db reference
		$this->_db = null;
	}

	public function dbConnect()
	{
		// open mysql connection
		$this->db = mysql_connect($this->config['host'], $this->config['username'], $this->config['password']);
		if (!$this->db) {
			throw new Exception('An error occurred attempting to connect to MySQL.');
		}

		// use specified database
		mysql_select_db($this->config['dbname']);

		// only allow UTF-8 characters
		mysql_query('SET NAMES "utf8"', $this->db);
	}

	/**
	 * Function which begins the crawler.  The site to be crawled
	 * depends on the configuration variable baseCrawlUrl and the
	 * current environment.
	 *
	 * @access 	public
	 * @param	int		$client_id
	 * @return	void
	 */
	public function crawl()
	{
        // truncate old entries
		$this->resetIndex();

		// begin crawling the url
		$this->crawlUrl($this->startUrl);
	}

	/**
	 * Function which crawls the given URL and parses the data looking for
	 * any links.  If a link is found to be using the same domain name or
	 * is a relative URL, it is recursively parsed as well.
	 *
	 * @access	public
	 * @param	string	$url
	 * @return	void
	 */
	public function crawlUrl($url)
	{
		error_log('Crawling url: ' . $url);

		// check if the page is valid, if so parse its data
		$data = $this->getPageData($url);
		if ($data !== false) {

            // notify the child class that a url has been found
            $this->onUrlFound($url, $data);

			// find all urls on the page
			$urlMatches = array();
			if (preg_match_all('/href="([^#"]+)"/i', $data, $urlMatches, PREG_PATTERN_ORDER)) {

				// garbage collect url matches
				$urlMatches[0] = null;

				// garbage collect HTML
				unset($data);

				// iterate over each link on the page
				foreach ($urlMatches[1] as $k => $link) {

					// dont index any of these extensions
					if ($this->_ignoreExtension($link)) {
						continue;
					}

					// dont allow access to any ignored files
					if ($this->_ignoreFile($link)) {
						continue;
					}

					// determine if we have a relative path
					$isRelativePath = strpos($link, 'http') === false ? true : false;
					if ($isRelativePath) {

						// if the file exists, skip
						if (strlen($link) > 1 && file_exists(BASE_PATH . '/' . ltrim($link, '/'))) {
							continue;
						}

						// don't allow more than maxDepth forward slashes in the URL
						if ($this->maxDepth > 0 && (substr_count($link, '/') > $this->maxDepth)) {
							continue;
						}

						// relative path starting with forward slash
						if (strpos($link, '/') === 0) {

							// update the link with the full domain path
							$link = (strlen($link) > 0) ? $this->baseUrl . $link : $this->baseUrl;

							// check to see if the link has already been added
							if ($this->checkUrlExists($link)) {
								continue;
							}

							// the link is new so crawl it
							$this->crawlUrl($link);

						} else {
							// path should be relative to the base url
							continue;
						}

					// check for urls within the same (sub) domain
					} else if (strpos($link, $this->baseUrl) !== false) {

						// check to see if the link has already been added
						if ($this->checkUrlExists($link)) {
							continue;
						}

						// the link is new so crawl it
						$this->crawlUrl($link);

					// no matches whatsoever
					} else {
						continue;
					}

				}

				// garbage collect
				unset($urlMatches);

			}

		}

	}

	/**
	 * Function to check whether a link already exists in the database or not.
	 * If the link exists the count is updated.
	 *
	 * @access	public
	 * @param	string	$link
	 * @param	bool	$tryReconnect
	 * @return	bool
	 */
	protected function checkUrlExists($link, $tryReconnect = false)
	{
		$sql = sprintf('SELECT 1
						FROM crawl_index
						WHERE client_id = %d
						AND link = "%s"',
						$this->client_id,
						mysql_real_escape_string($link));

		$res = mysql_query($sql, $this->db);
		if ($res && mysql_num_rows($res) > 0) {
			mysql_free_result($res);
			$sql = sprintf('UPDATE crawl_index
							SET count = count + 1
							WHERE client_id = %d
							AND link = "%s"',
							$this->client_id,
							mysql_real_escape_string($link));

			mysql_query($sql, $this->db);
			return true;
		} else if (!$res) {
			error_log('Invalid query: ' . mysql_error());
			if (!$tryReconnect) {
				$this->dbConnect();
				return $this->checkUrlExists($link, true);
			}
		}
		return false;
	}

	/**
	 * When a URL is found, add it to the index.
	 *
	 * @access	protected
	 * @param	string		$url
	 * @param	string		$data
	 */
    protected function onUrlFound($url, $data)
    {
        $this->addUrlToIndex($url, md5($data));
    }

	/**
	 * Function to add a given URL to the crawled index table.
	 *
	 * @access 	public
	 * @param	string	$url
	 * @return	void
	 */
	public function addUrlToIndex($url, $hash)
	{
		// the link doesnt exist in the db, insert now
		$sql = sprintf('INSERT IGNORE INTO crawl_index SET
					   client_id = %d,
					   link = "%s",
					   `count` = 1,
					   contenthash = "%s"',
					   $this->client_id,
					   mysql_real_escape_string($url),
					   mysql_real_escape_string($hash));

		mysql_query($sql, $this->db);
		return mysql_insert_id();
	}

	/**
	 * Removes all old entries of the sitemap from the database.
	 *
	 * @access 	public
	 * @return	void
	 */
	public function resetIndex()
	{
		$sql = sprintf('DELETE FROM crawl_index WHERE client_id = %d', $this->client_id);
		mysql_query($sql, $this->db);
	}

	/**
	 * Function which verifies a page exists prior to returning
	 * the HTML from the page.
	 *
	 * @access 	public
	 * @param	string	$link
	 * @return	mixed
	 */
	public function getPageData($link)
	{
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $link);
		curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 20);

		// handle HTTPS links if necessary
		if (strpos($link, 'https') !== false) {
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST,  1);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		// get the data response
		$response = curl_exec($this->ch);

		// get the response code
		$statusCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		// close connection
		curl_close($this->ch);

		// return data if status code matches
		return ($statusCode >= 200 && $statusCode < 400) ? $response : false;
	}

	/**
	 * Check if the link contains an ignored extension.
	 *
	 * @access	protected
	 * @param	string		$link
	 * @return	bool
	 */
	protected function _ignoreExtension($link)
	{
		$pos = strrpos($link, '.');
		if ($pos !== false) {
			$ext = substr($link, $pos + 1);
			if (in_array($ext, $this->_ignoredExtensions)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the link contains an ignored file.
	 *
	 * @access	protected
	 * @param	string		$link
	 * @return	bool
	 */
	protected function _ignoreFile($link)
	{
		foreach ($this->_ignoredFiles as $ignored) {
			if (strpos($link, $ignored) !== false) {
				return true;
			}
		}
		return false;
	}

}
