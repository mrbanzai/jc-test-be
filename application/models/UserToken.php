<?php
class UserToken extends Zend_Db_Table
{

    protected $_name 	= 'users_tokens';
	protected $_primary = 'id';

    /**
     * Ensures that a supplied activation token is valid. Checks length,
     * expiry, and usage.
     *
     * @access  public
     * @param   string  $token
     */
    public function validate($token) {
		$select = $this->select()
						->setIntegrityCheck(false)
						->from('users_tokens')
						->where('id = ?', $token);

		$result = $this->fetchRow($select);
        if (empty($result)) {
            return 'invalid';
        }

		// convert to array
		$result = $result->toArray();

        // check if expired
        if ($result['expires'] != NULL && time() > (int) $result['expires']) {
            return 'expired';
        }

        // check if max usage exceeded
        if ($result['max_usage'] > 0 && (((int) $result['usage'] + 1) >= (int) $result['max_usage'])) {
            return 'usage';
        }
        return TRUE;
    }

	/**
	 * Generates user tokens for granting beta access through the signup form.
	 *
	 * @access	public
	 * @param	int		$user_id
	 * @param	int		$expires
	 * @param	int		$max_usage
	 * @return	void
	 */
	public function generate($user_id, $expires = NULL, $max_usage = 0) {
		// check for pre-existing
		$select = $this->select()->from('users_tokens', array('id'))->where('user_id = ?', $user_id);
		$exists = $this->fetchRow($select);
		if (!empty($exists->id)) return $exists->id;

		// generate a unique UUID
		$uuid = sprintf( '%04x%04x%04x%04x%04x%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		// grab the expiration
		$expires = !$expires ? NULL : time() + (3600 * (int) $expires);

		// generate the insert parameters
		$array = array(
			'id'		    => $uuid,
			'user_id'		=> $user_id,
			'max_usage'     => $max_usage,
			'usage'		    => 0,
			'expires'	    => $expires,
            'created_ts'    => time()
		);

        // save the token
		if ($this->insert($array)) {
			return $uuid;
		}
		return FALSE;
	}

}