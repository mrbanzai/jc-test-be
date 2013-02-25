<?php
class Ats_Feed extends Skookum_Model
{

	/**
	 * Add or edit an ATS feed.
	 *
	 * @access	public
	 * @param	array	$post
	 * @param	int		$created_by
	 * @param	mixed	$atsFeedId
	 */
	public function update($post, $created_by = NULL, $atsFeedId = NULL)
	{
		$time = time();

		// merge the user id
		$post['user_id'] = $created_by;

        // validate the data
		$editing = !empty($atsFeedId);
        $this->validate($post, $editing);

		// storage data
		$data = array(
			'feed_type_id' => $post['ats_type_id'],
			'user_id' => $created_by,
			'name' => $post['name'],
			'url' => $post['url'],
			'default_modal_style' => $post['ats_default_modal_style'] ?: null,
			'created_ts' => $time,
			'modified_ts' => $time
		);

		// check if adding or updating
		if ($editing) {
			$where = sprintf('id = %d', $atsFeedId);
			unset($post['id'], $post['created_ts']);
			return $this->_db->update('ats_feed', $data, $where);
		} else {
			if ($this->_db->insert('ats_feed', $data)) {
				return $this->_db->lastInsertId();
			}
			return false;
		}
	}

	/**
	 * Retrieve all feeds.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll()
    {
		// only attempt if we haven't ran within the hour
		$day = time() - 3600;

		$sql = sprintf('SELECT * FROM
						(SELECT * FROM ats_feed
						WHERE last_ran IS NULL
						UNION
						SELECT * FROM ats_feed
						WHERE last_ran < %d) AS feed
						ORDER BY last_ran ASC',
						$day);

		return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Retrieve all ATS feeds by ATS type.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getAllByAtsType($type_id)
	{
		// only attempt if we haven't ran within the hour
		$day = time() - 3600;

		$sql = sprintf('SELECT * FROM
						(SELECT ats_feed.*, users.subdomain
						FROM ats_feed
						INNER JOIN users ON (ats_feed.user_id = users.id)
						WHERE ats_feed.last_ran IS NULL
						AND ats_feed.feed_type_id = %1$d
						UNION
						SELECT ats_feed.*, users.subdomain
						FROM ats_feed
						INNER JOIN users ON (ats_feed.user_id = users.id)
						WHERE ats_feed.last_ran < %2$d
						AND ats_feed.feed_type_id = %1$d) AS feed
						ORDER BY last_ran ASC',
						$type_id,
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
        $sql = sprintf('SELECT * FROM ats_feed WHERE id = %d', $id);
        return $this->_db->query($sql)->fetch();
    }

    /**
     * Retrieve an ATS type by user id.
     *
     * @access  public
     * @param   int     $id
     */
    public function getByUserId($user_id)
    {
        $sql = sprintf('SELECT * FROM ats_feed WHERE user_id = %d LIMIT 1', $user_id);
        return $this->_db->query($sql)->fetch();
    }

	/**
	 * Updates the last ran timestamp for the given ATS feed to ensure
	 * it doesn't run too frequently.
	 *
	 * @access	public
	 * @param	int		$id
	 */
	public function updateLastRanTimestamp($id)
	{
		$sql = sprintf('UPDATE ats_feed SET last_ran = %d WHERE id = %d',
						time(),
						$id);

		return $this->_db->query($sql);
	}

    /**
     * Validation rules for ats feed creation and update.
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

        // user id only required for updates
        if ($is_update) {
            $validator
                ->required('You mus tprovide an ATS Feed ID for editing.')
                ->integer('Your ATS Feed ID appears to be invalid.')
                ->validate('id', 'ATS Feed ID');
        }

		// ensure we have a creator
        $validator
            ->required('You must be logged in to add a new user ATS.')
			->integer('Your User ID appears to be invalid.')
            ->validate('user_id', 'User ID');

		// ensure we have a feed type
        $validator
            ->required('You must select an ATS type.')
			->integer('Your ATS type appears to be invalid.')
            ->validate('ats_type_id', 'ATS Type');

		// ensure we have a name
        $validator
            ->required('You must name your ATS.')
            ->validate('name', 'Name');

		// ensure we have a valid url
        $validator
            ->required('You must provide an ATS url.')
			->url('You must provide a valid ATS url.')
            ->validate('url', 'Url');

        // check for errors
        if ($validator->hasErrors()) {
            throw new Skookum_Form_Validator_Exception(
                'An error occurred on form submission.',
                $validator->getAllErrors()
            );
        }

        return $validator->getValidData();
    }

}