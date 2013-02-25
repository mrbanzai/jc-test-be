<?php
class Sitemap extends Skookum_Model
{
    /**
     * Retrieve a sitemap file for a given subdomain.
     *
     * @access  public
     * @return  string
     */
    public function get($subdomain)
    {
		$sql = sprintf('SELECT sitemaps.sitemap
						FROM users
						INNER JOIN sitemaps ON (users.id = sitemaps.created_by)
						WHERE users.subdomain = %s',
						$this->_db->quote($subdomain));

        $result = $this->_db->query($sql)->fetch();
        if ($result) {
            return $result['sitemap'];
        } else {
			$text	= '<?xml version="1.0" encoding="UTF-8"?>';
			$text	.= '<sitemapindex
						xmlns="http://www.google.com/schemas/sitemap/0.84"
						xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
						xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/siteindex.xsd">' . "\n";
			$text	.= "<sitemap></sitemap>\n";
			$text	.= '</sitemapindex>';
            return $text;
        }
    }

}
