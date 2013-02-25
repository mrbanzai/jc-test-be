<?php
class Skookum_Caterpillar_Sitemap extends Skookum_Caterpillar_Crawler
{

	/**
	 * Crawl and index the website specified in the config.
	 *
	 * @access	public
	 * @return	void
	 */
    public function crawl()
    {
		// run the crawler
        parent::crawl();

        // build the updated site map
        $this->build();
    }

	/**
	 * Function which takes links stored in the crawl_index mysql table
	 * and creates a sitemap.xml file based on those.  Priorities are
	 * calculated based on the number of forward slashes contained in
	 * the URL.  Each forward slash decrements the priority by 0.1.  The
	 * priority can only be decremented by a maximum of maxDepth.  As such,
	 * maxDepth must be less than 10 as you cannot have a negative priority.
	 *
	 * @access 	public
	 * @return 	void
	 */
	public function build()
	{
		// get all links from the db, sorted by count
		$sql = sprintf('SELECT link, `count` FROM crawl_index WHERE client_id = %d', $this->client_id);
		$res = mysql_query($sql, $this->db);

		// get the current time to use as the last modification (EST offset)
		$time = date ("Y-m-d\TH:i:s") . '+00:00';

		// get the total number of links
		$num_links = mysql_num_rows($res);
		if ($num_links > 0) {

			// generate the sitemap file
			$text	= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$text	.= '<urlset xmlns="http://www.google.com/schemas/sitemap/0.9">' . "\n";

			while ($r = mysql_fetch_assoc($res)) {

				// skew priorities for actual job pages
				if (strpos($r['link'], 'job/details') !== FALSE) {
					$priority = 0.8;
				} else {
					// calculate priority based on the number of forward slashes
					$relative_url = str_replace($this->baseScheme . '://' . $this->startUrl, '', $r['link']);
					if (strlen($relative_url) == 1) {
						$priority = 1.0;
					} else {
						$priority = 1.0 - (0.1 * substr_count($relative_url, '/'));
					}
				}

				$text	.= '<url>';
				$text	.= '<loc>' . $this->_encodeXml($r['link']) . '</loc>';
				$text	.= '<lastmod>' . $time . '</lastmod>';
				$text	.= '<changefreq>' . $this->changeFrequency . '</changefreq>';
				$text	.= '<priority>' . $priority . '</priority>';
				$text	.= '</url>' . "\n";
			}

			$text	.= "</urlset>";

			// add or edit the sitemap entry
			try {
				$sql = sprintf('INSERT INTO sitemaps SET
								created_by = %1$d,
								sitemap = "%2$s"
								ON DUPLICATE KEY UPDATE
								sitemap = "%2$s"',
								$this->client_id,
								mysql_real_escape_string($text));

				mysql_query($sql, $this->db);
			} catch (Exception $e) {
				// do nothing
				error_log($e->getMessage());
			}

			mysql_free_result($res);

		}

	}

	/**
	 * Delete a sitemap. Important for deleted clients.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function delete()
	{
		// delete all references to a particular client
		$sql = sprintf('DELETE FROM crawl_index WHERE client_id = %d', $this->client_id);
		mysql_query($sql, $this->db);
	}

	/**
	 * Ensure proper encoding exists on the XML data.
	 *
	 * @access	private
	 * @param	string	$str
	 * @return	string
	 */
	private function _encodeXml($str)
	{
        return str_replace(
			array( '&', '"', "'", '<', '>'),
			array( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;'),
			$str
		);
	}

}
?>
