<?php
class Auth_Model_User extends Skookum_Model_Auth
{

	/**
	 * Checks if a given user exists based on their email address.
	 *
	 * @access	public
	 * @param	$email
	 * @return	bool
	 */
	public function exists($email)
	{
		$stmt = $this->_db->query('SELECT users.id, password_recovery.id AS pr_id
								 FROM users
								 LEFT JOIN password_recovery ON (users.id = password_recovery.user_id)
								 WHERE users.email = ?',
								 array($email));

		return $stmt->fetchObject();
	}

	/**
	 * Create an entry in the password_recovery table and email the user
	 * to handle a password reset. Make note that these password recoveries
	 * expire within an hour.
	 *
	 * @access	public
	 * @param	string	$email
	 * @return	mixed
	 */
	public function forgot_password($email)
	{
		// check if the user exists
		$user = $this->exists($email);
		if (is_object($user)) {

			// generate a unique id
			$unique_id = md5(uniqid(rand(), TRUE));

			// determine if a password_recovery request already exists
			if (is_numeric($user->pr_id)) {
				// update the password recovery entry
				$sql = 'UPDATE password_recovery SET unique_id = ?, expires = ? WHERE id = ?';
				$stmt = $this->_db->query($sql, array($unique_id, time() + 3600, $user->pr_id));
			} else {
				// set expires to 1 hour
				$expiry = time() + 3600;

				// update the password recovery entry
				$sql = 'INSERT INTO password_recovery SET
						user_id = ?,
						unique_id = ?,
						expires = ?
						ON DUPLICATE KEY UPDATE
						unique_id = ?,
						expires = ?';

				try {
					if ($this->_db->query(
						$sql,
						array($user->id, $unique_id, $expiry, $unique_id, $expiry)
					)) {
						// disable the user account
						 $this->_db->query('UPDATE users SET disabled = 1 WHERE id = ?', array($user->id));

						// return the unique identifier
						return $unique_id;
					}
				} catch (Exception $e) {
					return false;
				}

			}

		}

		return false;
	}

	/**
	 * Handle resetting a user's password.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function reset_password($unique_id, $values = array())
	{
		// if we don't have values, assume we're just checking if the unique_id is valid
		$status_code = $this->_validate_reset($unique_id);
		if (empty($values) || $status_code !== true) {
			return $status_code;
		}

		// ensure the passwords match
		if (empty($values['password']) || empty($values['password_confirm'])) {
			// must suppy password
			return 3;
		} else if ($values['password'] != $values['password_confirm']) {
			// passwords must match
			return 4;
		}

		// create a new unique salt
		$salt = '';

		// secure the password
		$password = $this->create_hash($values['password'], $salt, 10);

		// handle resetting the user's password and enabling the account
		$sql = 'UPDATE users
				INNER JOIN password_recovery ON (users.id = password_recovery.user_id)
				SET users.disabled = 0,
				users.passphrase = ?,
				users.salt = ?
				WHERE password_recovery.unique_id = ?';

		$stmt = $this->_db->query($sql, array($password, $salt, $unique_id));
		$stmt->execute();

		// delete the old reset entry
		$stmt = $this->_db->query('DELETE FROM password_recovery WHERE unique_id = ?', array($unique_id));
		$stmt->execute();

		// return success
		return true;
	}

	/**
	 * Handles validation for resetting user passwords.
	 *
	 * @access	private
	 * @return	int
	 */
	private function _validate_reset($unique_id)
	{
		$stmt = $this->_db->query('SELECT id, expires FROM password_recovery WHERE unique_id = ?', array($unique_id));
		$result = $stmt->fetchObject();
		if ($result) {
			// check if the timestamp is within reason
			if ($result->expires >= time()) {
				// the password reset is still valid
				return true;
			} else {
				// remove the entry
				$stmt = $this->_db->query('DELETE FROM password_recovery WHERE unique_id = ?', array($unique_id));
				$stmt->execute();

				// reset has expired, status code 1
				return 1;
			}
		}

		// no match found, status code 2
		return 2;
	}

}