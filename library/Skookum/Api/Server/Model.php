<?php
class Skookum_Api_Server_Model extends Skookum_Model
{

    /**
     * Retrieves API key details for a given user account.
     *
     * @access  public
     * @param   int     $user_id
     * @return  mixed
     */
    public function getByUserId($user_id)
    {
        $sql = sprintf('SELECT api_keys.*
                       FROM users
                       INNER JOIN api_keys ON (users.id = api_keys.user_id)
                       WHERE users.id = %d',
                       $user_id);

        return $this->_db->query($sql)->fetch();
    }

    /**
     * Generates a public/private key pairing for a given domain.
     *
     * @access  public
     * @param   string  $domain
     * @param   int     $user_id
     * @return  array('public' => '', 'private' => '')
     */
    public function generateKeyPair($domain, $user_id)
    {
        // get the created time
        $time = time();

        // verify the domain
        $domain = $this->validateDomain($domain);
        if (!$domain) {
            return FALSE;
        }

        // generate a public and private key
        $public = sha1(uniqid('', TRUE));
        $private = sha1(uniqid('', TRUE));

        // create the database entry
        $insertData = array(
            'user_id' => $user_id,
            'domain' => $domain,
            'public' => $public,
            'private' => $private,
            'created_ts' => $time
        );

        if ($this->_db->insert('api_keys', $insertData)) {
            // create an hourly limit entry
            $insertData = array(
                'key_id' => $this->_db->lastInsertId(),
                'max_requests' => 5,
                'num_requests' => 0,
                'reset_ts' => $time
            );

            if ($this->_db->insert('api_limit', $insertData)) {
                return array('public' => $public, 'private' => $private);
            }
        }

        return FALSE;
    }

    /**
     * Re-generates a public/private key pairing for a given domain.
     *
     * @access  public
     * @param   string  $domain
     * @param   int     $user_id
     * @return  array('public' => '', 'private' => '')
     */
    public function regenerateKeyPair($domain, $user_id)
    {
        // get the created time
        $time = time();

        // verify the domain
        $domain = $this->validateDomain($domain);
        if (!$domain) return FALSE;

        // generate a public and private key
        $public = sha1(uniqid('', TRUE));
        $private = sha1(uniqid('', TRUE));

        // create the database entry
        $updateData = array(
            'public' => $public,
            'private' => $private,
            'modified_ts' => $time
        );

        if ($this->_db->update('api_keys', $updateData, sprintf('user_id = %d', $user_id))) {
            return array('public' => $public, 'private' => $private);
        }

        return FALSE;
    }

    /**
     * Update the domain for a given user.
     *
     * @access  public
     * @param   string  $domain
     * @param   int     $user_id
     * @return  mixed
     */
    public function updateDomain($domain, $user_id)
    {
        // get the created time
        $time = time();

        // verify the domain
        $domain = $this->validateDomain($domain);
        if (!$domain) return FALSE;

        // create the database entry
        $updateData = array(
            'domain' => $domain,
            'modified_ts' => $time
        );

        if ($this->_db->update('api_keys', $updateData, sprintf('user_id = %d', $user_id))) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Validates a public key for the given domain, ensuring it is correct.
     *
     * @access  public
     * @param   string  $public
     * @param   string  $referrer
     * @return  string|false    Returns the private key on success
     */
    public function validatePublicKey($public, $referrer)
    {
        $sql = sprintf('SELECT user_id, private, domain
                        FROM api_keys
                        WHERE public = %s
                        LIMIT 1',
                        $this->_db->quote($public));

        $result = $this->_db->query($sql)->fetch();
        if ($result) {
            // verify the domains match
            if (strpos($referrer, $result['domain']) !== FALSE) {
                return $result;
            }
        }

        return FALSE;
    }

    /**
     * Ensure that the domain is, in fact, a valid one.
     *
     * @access  public
     * @param   string  $domain
     * @return  string
     */
    private function validateDomain($domain)
    {
        // verify the domain
        if (!preg_match('@^http?s://@i', $domain)) {
            $domain = 'http://' . $domain;
        }

        if (filter_var($domain, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED) === false) {
            return FALSE;
        }

        return parse_url($domain, PHP_URL_HOST);
    }

}
