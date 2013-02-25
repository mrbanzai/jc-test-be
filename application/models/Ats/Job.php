<?php
class Ats_Job extends Skookum_Model
{

	// if a job isnt pulled after two attempts, it bombed
	const MAX_FAIL_ATTEMPTS = 2;

	/**
	 * Retrieve all jobs that aren't closed or deleted.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll()
    {
		$sql = sprintf('SELECT ats_jobs.*, theme.company AS client, users.subdomain
						FROM ats_jobs
						INNER JOIN users ON (ats_jobs.created_by = users.id)
						INNER JOIN theme ON (ats_jobs.created_by = theme.created_by)
						WHERE ats_jobs.closed = 0
						AND ats_jobs.deleted = 0
						ORDER BY ats_jobs.modified_ts DESC');

		return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Retrieve all jobs by a particular location. Also assumes jobs
	 * must be pulled by a particular client subdomain. Will not receive
	 * closed or deleted jobs.
	 *
	 * @access	public
	 * @param	string	$location
	 * @param	string	$subdomain
	 * @return	array
	 */
	public function getAllByLocation($location, $subdomain)
	{
        $sql = sprintf('SELECT ats_jobs.*, users.subdomain
						FROM users
						INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
						WHERE users.subdomain = %s
						AND ats_jobs.location = %s
						AND ats_jobs.closed = 0
						AND ats_jobs.deleted = 0
						ORDER BY ats_jobs.modified_ts DESC',
						$this->_db->quote($subdomain),
						$this->_db->quote($location));

        return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Retrieve all jobs by a particular category. Also assumes jobs
	 * must be pulled by a particular client subdomain. Will not receive
	 * closed or deleted jobs.
	 *
	 * @access	public
	 * @param	string	$category
	 * @param	string	$subdomain
	 * @return	array
	 */
	public function getAllByCategory($category, $subdomain)
	{
        $sql = sprintf('SELECT ats_jobs.*, users.subdomain
						FROM users
						INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
						WHERE users.subdomain = %s
						AND ats_jobs.category = %s
						AND ats_jobs.closed = 0
						AND ats_jobs.deleted = 0
						ORDER BY ats_jobs.modified_ts DESC',
						$this->_db->quote($subdomain),
						$this->_db->quote($category));

        return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Retrieves all jobs by user id.
	 *
	 * @access	public
	 * @param	int		$user_id
	 * @return	array
	 */
	public function getAllByUserId($user_id)
	{
		$sql = sprintf('SELECT *
						FROM ats_jobs
						WHERE created_by = %d
						AND deleted = 0
						ORDER BY modified_ts DESC',
						$user_id);

		return $this->_db->query($sql)->fetchAll();
	}

    /**
     * Retrieve an ATS type by id.
     *
     * @access  public
     * @param   int     $id
     * @return	mixed
     */
    public function getById($id)
    {
        $sql = sprintf('SELECT ats_jobs.*, users.subdomain
						FROM ats_jobs
						INNER JOIN users ON (ats_jobs.created_by = users.id)
						WHERE ats_jobs.id = %d',
						$id);

        return $this->_db->query($sql)->fetch();
    }

	/**
	 * Retrieves a job securely by forcing a matching created_by identifier.
	 *
	 * @access	public
	 * @param	int		$id
	 * @param	int		$created_by
	 * @return	mixed
	 */
	public function getByIdSecure($id, $created_by)
	{
        $sql = sprintf('SELECT ats_jobs.*, users.subdomain
						FROM ats_jobs
						INNER JOIN users ON (ats_jobs.created_by = users.id)
						WHERE ats_jobs.id = %d
						AND ats_jobs.created_by = %d
						AND ats_jobs.editable = 1',
						$id,
						$created_by);

        return $this->_db->query($sql)->fetch();
	}

	/**
	 * Retrieves a specific job for a particular client. Will not retrieve
	 * closed or deleted jobs.
	 *
	 * @access	public
	 * @param	string	$uristub
	 * @param	string	$subdomain
	 * @return	mixed
	 */
	public function getByUristub($uristub, $subdomain)
	{
        $sql = sprintf('SELECT ats_jobs.*, users.subdomain
						FROM users
						INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
						WHERE users.subdomain = %s
						AND ats_jobs.uristub = %s
						AND ats_jobs.closed = 0
						AND ats_jobs.deleted = 0',
						$this->_db->quote($subdomain),
						$this->_db->quote($uristub));

        return $this->_db->query($sql)->fetch();
	}

    /**
     * Retrieve the most recent job postings. Will not retrieve closed or
     * deleted jobs.
     *
     * @access  public
     * @param   int     $limit
     * @param	string	$subdomain
     * @return  array
     */
	public function getMostRecent($limit, $subdomain)
	{
		$limit = !empty($limit) ? sprintf('LIMIT %d', $limit) : '';

        $sql = sprintf('SELECT ats_jobs.name, ats_jobs.uristub, ats_jobs.category, ats_jobs.location, ats_jobs.date_posted,
						ats_jobs.schedule
						FROM users
						INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
						WHERE users.subdomain = %s
						AND ats_jobs.closed = 0
						AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.modified_ts DESC
                        %s',
						$this->_db->quote($subdomain),
                        $limit);

        return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Determines if a particular job already exists in the system.
	 *
	 * @access	public
	 * @param	int		$feed_id
	 * @param	mixed	$job_id
	 * @return	bool
	 */
	public function exists($feed_id, $job_id)
	{
		$sql = sprintf('SELECT ats_jobs.id
						FROM ats_feed
						INNER JOIN ats_jobs ON (ats_feed.id = ats_jobs.feed_id)
						WHERE ats_feed.id = %d
						AND ats_jobs.job_id = %s',
						$feed_id,
						$this->_db->quote($job_id));

		$result = $this->_db->query($sql)->fetch();
		return !empty($result) ? $result['id'] : false;
	}

	/**
	 * Add or edit a job.
	 *
	 * @access	public
	 * @param	array	$values
	 */
	public function addEdit($values)
	{
		// the current time
		$time = time();

		// determine if updating
		$is_update = !empty($values['id']);

		// ensure the data submitted is valid
		$this->validate($values, $is_update);

		try {

			// generate the unique uristub
			$str = $values['name'] . (isset($values['job_id']) ? '-' . $values['job_id'] : '');
			if(!isset($values['uristub']) || empty($values['uristub'])) {
                $uristub = $this->_generateUristub('ats_jobs', 'uristub', $str, 155);
            }
            else $uristub = $values['uristub'];

			$sql = sprintf('INSERT INTO ats_jobs SET
							id = %1$d,
							created_by = %2$d,
							feed_id = %3$d,
							job_id = %4$s,
							uristub = %5$s,
							company = %6$s,
							name = %7$s,
							location = %8$s,
							city = %9$s,
							state = %10$s,
							address = %11$s,
							zipcode = %12$s,
							category = %13$s,
							department = %14$s,
							schedule = %15$s,
							shift = %16$s,
							description = %17$s,
							qualifications = %18$s,
							num_openings = %19$d,
							years_exp = %20$d,
							job_url = %21$s,
							apply_url = %22$s,
							apply_phone = %23$s,
							dynamic_phone = %24$s,
							tracking_code = %25$s,
							outbound_link_url = %26$s,
							modal_style = %27$s,
							editable = 1,
							date_posted = %28$s,
							created_ts = %29$d,
							modified_ts = %29$d
							ON DUPLICATE KEY UPDATE
							company = %6$s,
							name = %7$s,
							location = %8$s,
							city = %9$s,
							state = %10$s,
							address = %11$s,
							zipcode = %12$s,
							category = %13$s,
							department = %14$s,
							schedule = %15$s,
							shift = %16$s,
							description = %17$s,
							qualifications = %18$s,
							num_openings = %19$d,
							years_exp = %20$d,
							apply_phone = %23$s,
							dynamic_phone = %24$s,
							tracking_code = %25$s,
							outbound_link_url = %26$s,
							modal_style = %27$s,
							failed_attempts = 0,
							date_posted = %28$s,
							modified_ts = %29$d',
							!empty($values['id']) ? $values['id'] : 'NULL',
							!empty($values['created_by']) ? $values['created_by'] : 'NULL',
							!empty($values['feed_id']) ? $values['feed_id'] : 'NULL',
							!empty($values['job_id']) ? $this->_db->quote($values['job_id']) : 'NULL',
							$this->_db->quote($uristub),
							!empty($values['company']) ? $this->_db->quote($values['company']) : 'NULL',
							$this->_db->quote($values['name']),
							!empty($values['location']) ? $this->_db->quote($values['location']) : 'NULL',
							!empty($values['city']) ? $this->_db->quote($values['city']) : 'NULL',
							!empty($values['state']) ? $this->_db->quote($values['state']) : 'NULL',
							!empty($values['address']) ? $this->_db->quote($values['address']) : 'NULL',
							!empty($values['zipcode']) ? $this->_db->quote($values['zipcode']) : 'NULL',
							!empty($values['category']) ? $this->_db->quote($values['category']) : 'NULL',
							!empty($values['department']) ? $this->_db->quote($values['department']) : 'NULL',
							!empty($values['schedule']) ? $this->_db->quote($values['schedule']) : 'NULL',
							!empty($values['shift']) ? $this->_db->quote($values['shift']) : 'NULL',
							!empty($values['description']) ? $this->_db->quote($values['description']) : 'NULL',
							!empty($values['qualifications']) ? $this->_db->quote($values['qualifications']) : 'NULL',
							!empty($values['num_openings']) ? (int) $values['num_openings'] : 'NULL',
							!empty($values['years_exp']) ? (int) $values['years_exp'] : 'NULL',
							!empty($values['job_url']) ? $this->_db->quote($values['job_url']) : $this->_db->quote('http://localhost'),
							!empty($values['apply_url']) ? $this->_db->quote($values['apply_url']) : (!empty($values['job_url']) ? $this->_db->quote($values['job_url']) : $this->_db->quote('http://localhost/apply')),
							!empty($values['apply_phone']) ? $this->_db->quote($values['apply_phone']) : 'NULL',
							!empty($values['dynamic_phone']) && $values['dynamic_phone'] != '0' ? '1' : '0',
							!empty($values['tracking_code']) ? $this->_db->quote($values['tracking_code']) : 'NULL',
							!empty($values['outbound_link_url']) ? $this->_db->quote($values['outbound_link_url']) : 'NULL',
							!empty($values['modal_style']) ? $this->_db->quote($values['modal_style']) : '\'apply\'',
							!emptY($values['date_posted']) ? date('U', strtotime($values['date_posted'])) : 'NULL',
							$time);

			$this->_db->getConnection()->exec($sql);
			if (!$is_update) {
				return $this->_db->lastInsertId();
			} else {
				return true;
			}

		} catch (Exception $e) {
			return false;
		}

	}

	/**
	 * Add all job listings to the feed listing cache.
	 *
	 * @access	public
	 * @param	int		$feed_id
	 * @param	array	$details
	 */
	public function update($feed_id, $details, $created_by)
	{
		$time = time();
		static $feedData = array();

		if (!isset($feedData[$feed_id])) {
			$AtsFeed = new Ats_Feed();
			$feed = $feedData[$feed_id] = $AtsFeed->getById($feed_id);
		} else {
			$feed = $feedData[$feed_id];
		}

		if (!empty($details)) {

			// generate the unique uristub
			$str = $details['name'] . (isset($details['job_id']) ? '-' . $details['job_id'] : '');
			$uristub = $this->_simpleUristub($str, 155);

			if (empty($details['modal_style']) && !empty($feed['default_modal_style']))
				$details['modal_style'] = $feed['default_modal_style'];

			// sanitize the name
			$title = htmlentities(strip_tags(trim($details['name'])));
			$sql = sprintf('INSERT INTO ats_jobs SET
							feed_id = %1$d,
							job_id = %2$s,
							created_by = %3$s,
							uristub = %4$s,
							company = %5$s,
							name = %6$s,
							location = %7$s,
							city = %8$s,
							state = %9$s,
							address = %10$s,
							zipcode = %11$s,
							category = %12$s,
							department = %13$s,
							schedule = %14$s,
							shift = %15$s,
							description = %16$s,
							qualifications = %17$s,
							num_openings = %18$d,
							years_exp = %19$d,
							job_url = %20$s,
							apply_url = %21$s,
							apply_phone = %22$s,
							dynamic_phone = %23$s,
							tracking_code = %24$s,
							outbound_link_url = %25$s,
							modal_style = %26$s,
							hide_apply = %27$d,
							editable = %28$d,
							date_posted = %29$s,
							created_ts = %30$d,
							modified_ts = %30$d
							ON DUPLICATE KEY UPDATE
							uristub = %4$s,
							company = %5$s,
							name = %6$s,
							location = %7$s,
							city = %8$s,
							state = %9$s,
							address = %10$s,
							zipcode = %11$s,
							category = %12$s,
							department = %13$s,
							schedule = %14$s,
							shift = %15$s,
							description = %16$s,
							qualifications = %17$s,
							num_openings = %18$d,
							years_exp = %19$d,
							job_url = %20$s,
							apply_url = %21$s,
							apply_phone = %22$s,
							dynamic_phone = %23$s,
							tracking_code = %24$s,
							outbound_link_url = %25$s,
							modal_style = %26$s,
							hide_apply = %27$d,
							editable = %28$d,
							failed_attempts = 0,
							closed = 0,
							deleted = 0,
							date_posted = %29$s,
							modified_ts = %30$d',
							$feed_id,
							!empty($details['job_id']) ? $this->_db->quote($details['job_id']) : 'NULL',
							$created_by,
							$this->_db->quote($uristub),
							!empty($details['company']) ? $this->_db->quote($details['company']) : 'NULL',
							$this->_db->quote($title),
							!empty($details['location']) ? $this->_db->quote($details['location']) : 'NULL',
							!empty($details['city']) ? $this->_db->quote($details['city']) : 'NULL',
							!empty($details['state']) ? $this->_db->quote($details['state']) : 'NULL',
							!empty($details['address']) ? $this->_db->quote($details['address']) : 'NULL',
							!empty($details['zipcode']) ? $this->_db->quote($details['zipcode']) : 'NULL',
							!empty($details['category']) ? $this->_db->quote($details['category']) : 'NULL',
							!empty($details['department']) ? $this->_db->quote($details['department']) : 'NULL',
							!empty($details['schedule']) ? $this->_db->quote($details['schedule']) : 'NULL',
							!empty($details['shift']) ? $this->_db->quote($details['shift']) : 'NULL',
							!empty($details['description']) ? $this->_db->quote($details['description']) : 'NULL',
							!empty($details['qualifications']) ? $this->_db->quote($details['qualifications']) : 'NULL',
							!empty($details['num_openings']) ? (int) $details['num_openings'] : 'NULL',
							!empty($details['years_exp']) ? (int) $details['years_exp'] : 'NULL',
							!empty($details['job_url']) ? $this->_db->quote($details['job_url']) : 'NULL',
							!empty($details['apply_url']) ? $this->_db->quote($details['apply_url']) : 'NULL',
							!empty($details['apply_phone']) ? $details['apply_phone'] : 'NULL',
							!empty($details['dynamic_phone']) && $details['dynamic_phone'] != '0' ? '1' : '0',
							!empty($details['tracking_code']) ? $details['tracking_code'] : 'NULL',
							!empty($details['outbound_link_url']) ? $details['outbound_link_url'] : 'NULL',
							!empty($details['modal_style']) ? $this->_db->quote($details['modal_style']) : 'NULL',
							!empty($details['hide_apply']) ? (int) $details['hide_apply'] : 0,
							!empty($details['editable']) ? (int) $details['editable'] : 0,
							!empty($details['date_posted']) ? date('U', strtotime($details['date_posted'])) : 'NULL',
							$time);

			$this->_db->getConnection()->exec($sql);
			return $this->_db->lastInsertId();
		}

		return false;
	}

	/**
	 * Increment the failed count for any jobs that didn't get updated after
	 * the feed finished running.
	 *
	 * @access	public
	 * @param	int		$feed_id
	 * @param	int		$startTime
	 */
	public function updateFailedCount($feed_id, $startTime)
	{
		$sql = sprintf('UPDATE ats_jobs
						SET failed_attempts = failed_attempts + 1
						WHERE feed_id = %d
						AND modified_ts < %d
						AND closed = 0',
						$feed_id,
						$startTime);

		return $this->_db->getConnection()->exec($sql);
	}

	/**
	 * Increments the failed count of a particular job. If the failed count
	 * hits a hard limit, we delete the job.
	 *
	 * @access	public
	 * @param	string	$job_url
	 */
	public function incrementFailedCount($job_url)
	{
		// get the current failed count
		$sql = sprintf('SELECT id, failed_attempts, closed, deleted
						FROM ats_jobs
						WHERE job_url = %s
						LIMIT 1',
						$this->_db->quote($job_url));

		$result = $this->_db->query($sql)->fetch();
		if ($result && isset($result['failed_attempts'])) {
			// skip if already closed or deleted
			if ($result['closed'] == 1 || $result['deleted'] == 1) {
				return FALSE;
			}

			// increment failed attempts
			$failed_attempts = (int) $result['failed_attempts'];
			if (++$failed_attempts >= self::MAX_FAIL_ATTEMPTS) {
				// we need to close the matching job
				$sql = sprintf('UPDATE ats_jobs
								SET closed = 1,
								failed_attempts = %d,
								modified_ts = %d
								WHERE id = %d',
								$failed_attempts,
								time(),
								$result['id']);

				$this->_db->query($sql);

				// return the job to remove it from the search index
				return $result['id'];
			} else {
				// we need to increment the failed attempts counter
				$sql = sprintf('UPDATE ats_jobs
								SET failed_attempts = %d,
								modified_ts = %d
								WHERE id = %d',
								$result['id'],
								time());

				$this->_db->query($sql);
			}
		}

		// no need to increment or do anything else, no match found
		return FALSE;
	}

	/**
	 * Retrieves any and all jobs that should be deleted from the system.
	 *
	 * @access	public
	 * @param	int		$feed_id
	 * @return	mixed
	 */
	public function closeJobs($feed_id)
	{
		// get all jobs with high failure counts
		$sql = sprintf('SELECT id, failed_attempts, closed, deleted
						FROM ats_jobs
						WHERE feed_id = %d
						AND failed_attempts >= %d',
						$feed_id,
						self::MAX_FAIL_ATTEMPTS);

		$results = $this->_db->query($sql)->fetchAll();
		if (!empty($results)) {
			// close matching jobs
			$sql = sprintf('UPDATE ats_jobs
							SET closed = 1
							WHERE feed_id = %d
							AND failed_attempts >= %d
							AND closed = 0',
							$feed_id,
							self::MAX_FAIL_ATTEMPTS);

			$this->_db->getConnection()->exec($sql);

			return $results;
		}

		return false;
	}

	/**
	 * Retrieve all potential jobs to close out.
	 *
	 * @access	public
	 * @param	int		$feed_id
	 * @return	mixed
	 */
	public function getClosedJobs($feed_id)
	{
		$sql = sprintf('SELECT id FROM ats_jobs
						WHERE feed_id = %d
						AND closed = 1
						AND deleted = 0',
						$feed_id);

		return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Garbage collect and clean up any old closed jobs. Assumes jobs that have
	 * been closed for 30 days can be deleted.
	 *
	 * @access	public
	 * @param	int		$feed_id
	 * @return	mixed
	 */
	public function deleteClosedJobs($feed_id)
	{
		$sql = sprintf('UPDATE ats_jobs
						SET deleted = 1
						WHERE feed_id = %d
						AND closed = 1
						AND deleted = 0
						AND modified_ts < %d',
						$feed_id,
						strtotime('-30 days'));

		return $this->_db->getConnection()->exec($sql);
	}

	/**
	 * Update a job's closed status.
	 *
	 * @access	public
	 * @param	int		$status
	 * @return	void
	 */
	public function setStatus($job_id, $status)
	{
		$status = $status == 1 ? 1 : 0;
		$where = sprintf('id = %s', $job_id);
		return $this->_db->update('ats_jobs', array('closed' => $status), $where);
    }

	/**
	 * Delete a job.
	 *
	 * @access	public
	 * @param	int		$job_id
	 * @return	void
	 */
	public function delete($job_id)
	{
		$where = sprintf('id = %s', $job_id);
		return $this->_db->update('ats_jobs', array('deleted' => 1), $where);
    }

    /**
     * Validation rules for job creation and update.
     *
     * @access  public
     * @param   array   $data
     * @param   bool    $is_update
     * @return  bool
     * @throws  Skookum_Form_Validator_Exception
     */
    public function validate(array $data = array(), $is_update = true)
    {
        $validator = $this->getValidator($data);

        // job id only required for updates
        if ($is_update) {
            $validator
                ->required('You must edit an existing job.')
                ->integer('The job you are attempting to edit does not appear to be valid.')
                ->validate('id', 'Job ID');
        }

		// ensure we have a title
        $validator
            ->required('You must enter a job title.')
            ->validate('name', 'Job Title');

		// ensure we have a location
        $validator
            ->required('You must enter a job location.')
            ->validate('location', 'Location');

		// ensure we have a category
        $validator
            ->required('You must enter a job category.')
            ->validate('category', 'Category');

		// ensure we have a category
        $validator
            ->required('You must enter a job description.')
            ->validate('description', 'Description');

        // check for errors
        if ($validator->hasErrors()) {
            throw new Skookum_Form_Validator_Exception(
                'An error occurred on form submission.',
                $validator->getAllErrors()
            );
        }

        return $validator->getValidData();
    }

    public function overrideModalStyle($modal_style, $feed_id = null) {
    	$where = $feed_id ? sprintf('feed_id = %d', $feed_id) : null;
    	return $this->_db->update('ats_jobs', array('modal_style' => $modal_style), $where);
    }

}

