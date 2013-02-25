<?php
class Users extends Skookum_Model_Auth
{

	const ROLE_SUPER = 0;
	const ROLE_ADMIN = 1;
	const ROLE_USER = 2;
	const ROLE_GUEST = 3;

	/**
	 * Retrieve all user roles.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll()
	{
		return $this->_db->query('SELECT * FROM users')->fetchAll();
	}

	/**
	 * Retrieves all users of a particular role.
	 *
	 * @access	public
	 */
	public function getAllUsersByRole($role_id)
	{
		$return = array();

		$sql = sprintf('SELECT id, firstname, lastname, company
						FROM users
						WHERE role_id = %d
						ORDER BY company, firstname ASC',
						$role_id);

		$result = $this->_db->query($sql)->fetchAll();
		if ($result) {
			foreach ($result as $r) {
				$return[$r['id']] = Clean::xss((!empty($r['company']) ? $r['company'] . ' - ' : '') . $r['firstname'] . ' ' . $r['lastname']);
			}
		}

		return $return;
	}

	/**
	 * Retrieve the most recent users from the database.
	 *
	 * @access	public
	 * @param	int		$limit
	 * @return	array
	 */
	public function getRecentUsers($limit = 10)
	{
		$sql = sprintf('SELECT id, firstname, lastname, company
					   FROM users
					   ORDER BY created_ts DESC
					   LIMIT %d',
					   $limit);

		return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Get details for a particular user by email.
	 *
	 * @access	public
	 * @param	string	$email
	 */
    public function getDetails($email)
	{
		// , avatar, avatar_thumb, avatarhash, avatartext, salt
		$sql = sprintf('SELECT id, role_id AS role, email, firstname, lastname, company, dynamic_phone_tracking, default_phone
						FROM users
						WHERE email = %s',
						$this->_db->quote($email));

		return $this->_db->query($sql)->fetch();
    }

	/**
	 * Get details for a user by id.
	 *
	 * @access	public
	 * @param	int		$id
	 */
	public static function getDetailsById($id)
	{
		// , avatar, avatar_thumb, avatarhash, avatartext, salt
		$sql = sprintf('SELECT id, role_id AS role, email, firstname, lastname, company, dynamic_phone_tracking, default_phone
						FROM users
						WHERE id = %d',
						$id);

		return $this->_db->query($sql)->fetch();
    }

    /**
     * Return enough data necessary to store in the user session.
     *
     * @access  public
     * @param   int     $user_id
     * @return  array
     */
    public function getById($user_id)
    {
        $user = array();
        $sql = sprintf('SELECT id, role_id AS role, subdomain, email, firstname, lastname, company, disabled, deleted, dynamic_phone_tracking, default_phone
                       FROM users
					   WHERE id = %d',
                       $user_id);

        return $this->_db->query($sql)->fetch();
    }

	/**
	 * Retrieves all clients and their associated subdomain for sitemap
	 * indexing and crawling.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getAllClientsForCrawl()
	{
		// , avatar, avatar_thumb, avatarhash, avatartext, salt
		$sql = sprintf('SELECT id, subdomain, deleted
						FROM users
						WHERE role_id = %d',
						Users::ROLE_USER);

		return $this->_db->query($sql)->fetchAll();
	}

	/**
	 * Checks if a user account exists, works for both id and email.
	 *
	 * @access	public
	 * @param	mixed	$id
	 * @return	int
	 */
	public function exists($id)
	{
		$field = (is_numeric($id)) ? 'id' : 'email';

		$sql = sprintf('SELECT 1
						FROM users
						WHERE %s = %s',
						$field,
						$this->_db->quote($id));

		return $this->_db->query($sql)->rowCount() > 0;
    }

	/**
	 * Checks if a client currently exists in the site.
	 *
	 * @access	public
	 * @param	mixed	$user_id
	 * @return	bool
	 */
	public function checkIfClientExists($user_id)
	{
		$sql = sprintf('SELECT COUNT(1) AS total
						FROM users
						WHERE role_id = %d
						%s',
						Users::ROLE_USER,
						!empty($user_id) ? sprintf('AND id != %d', $user_id) : '');

		$result = $this->_db->query($sql)->fetch();
		return $result['total'] > 0;
	}

    /**
     * Authenticates a user by email.
     *
     * @access  public
     * @param   array   $post
     * @return  mixed
     */
    public function authenticate(array $post = array())
    {
        $password = '';
        if (!empty($post['password'])) {
            $password = $post['password'];
        } else if (!empty($post['passphrase'])) {
            $password = $post['passphrase'];
        }

        $sql = sprintf('SELECT id, role_id AS role, email, firstname, lastname, company, passphrase, salt
                        FROM users
                        WHERE email = %s
                        AND disabled = 0
                        AND deleted = 0',
                       !empty($post['email']) ? $this->_db->quote($post['email']) : 'NULL',
                       !empty($password) ? $this->_db->quote($password) : 'NULL');

        $user = $this->_db->query($sql)->fetch();

		// validate that the passwords match
		if ($this->validate_hash($password, $user['passphrase'], $user['salt'])) {
			unset($user['passphrase'], $user['salt']);
			return $user;
		}

        return false;
    }

	/**
	 * Create a new user.
	 *
	 * @access	public
	 * @param	array	$values
	 * @param	int		$created_by
	 * @param	bool	$canChangeRole
	 * @return	mixed
	 */
    public function create($values, $created_by, $canChangeRole = FALSE)
	{
        // validate the data
        $this->validate($values, $canChangeRole, false);

		// storage for a unique salt
		$salt = '';

		// the current time
		$time = time();

		// generate storage data
        $data = array(
			'firstname'		=> $values['firstname'],
			'lastname'		=> $values['lastname'],
			'company'		=> $values['company'],
            'email' 		=> $values['email'],
			'passphrase'	=> $this->create_hash($values['password'], $salt, 10),
			'salt'			=> $salt,
            'role_id' 		=> ($canChangeRole && isset($values['role'])) ? (int) $values['role'] : Users::ROLE_USER,
            'dynamic_phone_tracking' => $values['dynamic_phone_tracking'],
            'default_phone' => $values['default_phone'],
			'created_ts'	=> $time,
			'modified_ts'	=> $time
        );

		// for clients, check on subdomain and cname entries
		if ($data['role_id'] == Users::ROLE_USER) {
			if (!empty($values['subdomain'])) {
				$data['subdomain'] = preg_replace('/[^a-zA-Z0-9]/', '-', $values['subdomain']);
			}

			if (!empty($values['cname'])) {
				$data['cname'] = $values['cname'];
			}
		}

		// catch any errors
		try {

			$result = $this->_db->insert('users', $data);
			if ($result) {
				// get the user id
				return $this->_db->lastInsertId();
			}

		} catch (Exception $e) {
			error_log($e->getMessage());
		}

		return false;
    }

	/**
	 * Update user data.
	 *
	 * @access	public
	 * @param	array	$values
	 * @param	string	$salt
	 * @param	bool	$canChangeRole
	 */
    public function update($values, $salt = '', $canChangeRole = FALSE)
	{
		$data = array();

		// only update pertinent data
		if (isset($values['id'])) $data['id'] = $values['id'];
		if (isset($values['firstname'])) $data['firstname'] = $values['firstname'];
		if (isset($values['lastname'])) $data['lastname'] = $values['lastname'];
		if (isset($values['company'])) $data['company'] = $values['company'];
		if (isset($values['email'])) $data['email'] = $values['email'];
		if (isset($values['dynamic_phone_tracking'])) $data['dynamic_phone_tracking'] = $values['dynamic_phone_tracking'];
		if (isset($values['default_phone'])) $data['default_phone'] = $values['default_phone'];
		if (isset($values['password'])) $data['password'] = $values['password'];
		if (isset($values['password2'])) $data['password2'] = $values['password2'];

		// handle potential case of role change
		if ($canChangeRole && isset($values['role'])) {
			// set the role id
			$data['role_id'] = (int) $values['role'];
			// if user being edited is client, allow subdomain changes
			if ($data['role_id'] == Users::ROLE_USER) {
				if (!empty($values['subdomain'])) {
					$data['subdomain'] = preg_replace('/[^a-zA-Z0-9-]/', '', $values['subdomain']);
				}

				if (!empty($values['cname'])) {
					$data['cname'] = $values['cname'];
				}
			}
		}

		// update the modified time
		$data['modified_ts'] = time();

		// validate the data
		$this->validate($data, $canChangeRole, true);

		// remove values we don't want
		unset($data['password'], $data['password2'], $data['id']);

		// handle potential password change case
		if (!empty($values['password']) && !empty($values['password2'])) {
			// manage the salt in case of changes
			$old_salt = $salt;

			// update the password hash
			$data['passphrase'] = $this->create_hash($values['password'], $salt, 10);

			// if the salt changed
			if ($old_salt != $salt) {
				$data['salt'] = $salt;
			}
		}

		// generate the where clause
		$where = sprintf('id = %d', $values['id']);

		// attempt to update the user
		return $this->_db->update('users', $data, $where);
    }

	/**
	 * Updates the avatar of a logged in user.
	 *
	 * @access	public
	 * @param	array	$values
	 * @param	int		$user_id
	 * @return	bool
	 */
	public function updateAvatar($values, $user_id)
	{
		$data = array('avatarext' => isset($values['avatarext']) ? $values['avatarext'] : 'jpg');
		$where = sprintf('id = %d', $user_id);
		return $this->_db->update('users', $data, $where);
	}

	/**
	 * Update the last visit timestamp of the user.
	 *
	 * @access	public
	 * @param	string	$email
	 * @return	void
	 */
	public function addVisit($email)
	{
		/*
		$data = array('lastvisit' => time());
		$where = sprintf('email = %s', $this->_db->quote($email));
		return $this->_db->update('users', $data, $where);
		*/
		return true;
    }

	/**
	 * Update the last visit timestamp of the user.
	 *
	 * @access	public
	 * @param	int		$id
	 * @return	void
	 */
	public function addVisitWithId($id)
	{
		/*
		$data = array('lastvisit' => time());
		$where = sprintf('id = %d', $id);
		return $this->_db->update('users', $data, $where);
		*/
		return true;
	}

	/**
	 * Change the user's role.
	 *
	 * @access	public
	 * @param	int		$role
	 * @param	string	$email
	 * @return	void
	 */
	public function changeRole($role, $email)
	{
		$data = array('role_id' => $role);
		$where = sprintf('email = %s', $this->_db->quote($email));
		return $this->_db->update('users', $data, $where);
    }

	/**
	 * Delete a user.
	 *
	 * @access	public
	 * @param	int		$user_id
	 * @return	void
	 */
	public function delete($user_id)
	{
		$where = sprintf('id = %s', $user_id);
		return $this->_db->delete('users', $where);
    }

    /**
     * Checks whether the specified email address already exists.
     *
     * @access  public
     * @param   string  $email
     * @param   int     $user_id
     * @return  bool
     */
    public function emailExists($email, $user_id)
    {
        $and = '';
        if (!is_null($user_id)) {
            $and = sprintf(' AND id != %d', $user_id);
        }
        $sql = sprintf('SELECT 1 FROM users
                        WHERE email = %s%s
                        AND deleted = 0
                        AND disabled = 0',
                       $this->_db->quote($email),
                       $and);

        return $this->_db->query($sql)->rowCount() > 0 ? false : true;
    }

    /**
     * Checks whether the specified email address already exists.
     *
     * @access  public
     * @param   string  $email
     * @return  bool
     */
    public function emailDeleted($email)
    {
        $sql = sprintf('SELECT 1 FROM users
                        WHERE email = %s
                        AND deleted = 1
                        OR disabled = 1',
                       $this->_db->quote($email));

        return $this->_db->query($sql)->rowCount() > 0 ? false : true;
    }

	/**
	 * Validate a website hostname to be used as a CNAME record for the client.
	 *
	 * @param	string	$host
	 * @return	bool
	 */
	public function validHostname($host)
	{
		$host = trim($host);
		if (empty($host)) {
			return true;
		}

		return preg_match('/^([a-zA-Z0-9\-]+.?)+\.[a-z]{2,6}$/', strtolower($host));
	}

    /**
     * Validation rules for user creation and update.
     *
     * @access  public
     * @param   array   $data
     * @param	bool	$isAdmin
     * @param   bool    $is_update
     * @return  bool
     * @throws  Skookum_Form_Validator_Exception
     */
    public function validate(array $data = array(), $isAdmin, $is_update = true)
    {
        $validator = $this->getValidator($data);

        // user id only required for updates
        if ($is_update) {
            $validator
                ->required('You must be logged in to update your account.')
                ->integer('You do not appear to be properly authenticated.')
                ->validate('id', 'User ID');
        }

		// ensure we have a first name
        $validator
            ->required('You must enter a first name.')
            ->validate('firstname', 'Firstname');

		// ensure we have a last name
        $validator
            ->required('You must enter a last name.')
            ->validate('lastname', 'Lastname');

		// admins must enter a subdomain when editing clients
		if ($isAdmin && !empty($data['role_id']) && $data['role_id'] == Users::ROLE_USER) {
			$validator
				->required('You must enter a subdomain.')
				->maxlength(20, 'Your subdomain must not exceed 20 characters in length.')
				->uristub('Your subdomain must consist of only letters, numbers, and dashes.')
				->validate('subdomain', 'Subdomain');

			// we can only use valid hostnames
			$validator
				->maxlength(120, 'Your CNAME must not exceed 120 characters in length.')
				->callback(
					'validHostname',
					array(&$this, 'validHostname'),
					'The hostname you have entered does not appear to be valid.
					It must not start with http:// and it must be in the form of
					jobs.mydomain.com or mydomain.com'
				)
				->validate('cname', 'Cname');
		}

		// watch for email deletions
        $validator
            ->required('You must enter an email address.')
            ->callback('emailDeleted',
                       array(&$this, 'emailDeleted'),
                       'The account associated with this email address has been deleted or disabled.
                       Please contact support if you would like to re-enable.',
                       (isset($data['id']) ? $data['id'] : NULL))
            ->callback('emailExists',
                       array(&$this, 'emailExists'),
                       'The email address specified already exists.',
                       (isset($data['id']) ? $data['id'] : NULL))
            ->validate('email', 'Email');

		// we require a password on creation
        if (!$is_update) {
			// ensure we have a valid email address
			$validator
				->required('You must enter an email address.')
				->email('You must enter a valid email address.')
				->validate('email', 'Email');

            $validator
                ->required('You must enter a password.')
                ->minlength(5, 'Your password must be at least 5 characters in length.')
				->matches('password2', 'Your passwords must match.')
                ->validate('password', 'Password');

            $validator
                ->required('You must enter a password.')
                ->minlength(5, 'Your password must be at least 5 characters in length.')
                ->validate('password2', 'Password 2');

			// ensure we have a valid ATS website
			$validator
				->url('You must enter a valid ATS url.')
				->validate('url', 'Url');
        } else {
			// ensure we have a valid email address
			$validator
				->email('You must enter a valid email address.')
				->validate('email', 'Email');

			// ensure we have a valid ATS website
			$validator
				->url('You must enter a valid ATS url.')
				->validate('url', 'Url');
		}

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
