<?php
/**
 * Cached storage of all ATS job feed listing entries.
 */
class Ats_Feed_Listing extends Skookum_Model
{

	/**
	 * Retrieve all user roles.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll()
    {
		// only attempt if we haven't ran within the hour
		$day = time() - 3600; // 86400 for day

		$sql = sprintf('SELECT * FROM
						(SELECT * FROM ats_feed_listing
						WHERE last_ran IS NULL
						UNION
						SELECT * FROM ats_feed_listing
						WHERE last_ran < %d) AS listing
						ORDER BY last_ran ASC',
						$day);

		return $this->_db->query($sql)->fetchAll();
	}

    /**
     * Retrieve an ATS type by id.
     *
     * @access  public
     * @param   int     $id
     */
    public function getById($id)
    {
        $sql = sprintf('SELECT * FROM ats_feed_listing WHERE id = %d', $id);
        return $this->_db->query($sql)->fetch();
    }

	/**
	 * Add all job listings to the feed listing cache.
	 *
	 * @access	public
	 * @param	int		$feedId
	 * @param	array	$jobDetailUrls
	 */
	public function updateAll($feedId, $jobDetailUrls)
	{
		$time = time();
		$values = array();
		foreach ($jobDetailUrls as $jobUrl) {
			$values[] = sprintf('(%1$d, %2$s, %3$d, %3$d)',
								$feedId,
								$this->_db->quote($jobUrl),
								$time);
		}

		if (!empty($values)) {
			$sql = sprintf('INSERT IGNORE INTO ats_feed_listing (feed_id, job_url, created_ts, modified_ts) VALUES %s',
						   implode(',', $values));

			return $this->_db->query($sql);
		}

		return false;
	}

	/**
	 * Updates the last ran timestamp for the given ATS feed listing to ensure
	 * it doesn't run too frequently.
	 *
	 * @access	public
	 * @param	string	$jobUrl
	 */
	public function updateLastRanTimestamp($jobUrl)
	{
		$sql = sprintf('UPDATE ats_feed_listing SET last_ran = %d WHERE job_url = %s',
						time(),
						$this->_db->quote($jobUrl));

		return $this->_db->query($sql);
	}

}
