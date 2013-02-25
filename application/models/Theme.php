<?php
class Theme extends Skookum_Model
{


    /**
     * Retrieve a subdomain by a given cname record.
     *
     * @access  public
     * @return  mixed
     */
    public function getSubdomainByCname($cname)
    {
        $sql = sprintf('SELECT subdomain FROM users WHERE cname = %s',
                        $this->_db->quote($cname));

        $result = $this->_db->query($sql)->fetch();
        return $result['subdomain'];
    }

    /**
     * Retrieves a theme for a given user.
     *
     * @access  public
     */
    public function getByUserId($user_id)
    {
        $sql = sprintf('SELECT * FROM theme WHERE created_by = %d', $user_id);
        return $this->_db->query($sql)->fetch();
    }

    /**
     * Create a new theme for the given user.
     *
     * @access  public
     * @param   int     $created_by
     * @return  mixed
     */
    public function create($created_by)
    {
        $sql = sprintf('INSERT INTO theme SET
                        created_by = %d,
                        company = "My Company",
						website = NULL,
                        logo = NULL',
                        $created_by);

        return $this->_db->query($sql);
    }

    /**
     * Add or edit a theme.
     *
     * @access  public
     * @param   array   $post
     * @return	mixed
     */
    public function addEdit($post)
    {
        // validate the data
        $this->validate($post);

		// storage data
		$data = array(
			'created_by' => $post['created_by'],
			'company' => $post['company'],
			'bgcolor' => !empty($post['bgcolor']) ? substr($post['bgcolor'], 0, 6) : 'FFFFFF',
			'fgcolor' => !empty($post['fgcolor']) ? substr($post['fgcolor'], 0, 6) : '000000',
			'link' => !empty($post['link']) ? substr($post['link'], 0, 6) : '5171ac',
			'link_hover' => !empty($post['link_hover']) ? substr($post['link_hover'], 0, 6) : '41619c',
			'bgbutton' => !empty($post['bgbutton']) ? substr($post['bgbutton'], 0, 6) : '000000',
			'bgbutton_hover' => !empty($post['bgbutton_hover']) ? substr($post['bgbutton_hover'], 0, 6) : '111111',
			'fgbutton' => !empty($post['fgbutton']) ? substr($post['fgbutton'], 0, 6) : 'FFFFFF',
			'heading' => !empty($post['heading']) ? substr($post['heading'], 0, 6) : '000000',
			'modified_ts' => time()
		);

		// handle possible website
		if (!empty($post['website'])) {
			$data['website'] = $post['website'];
		}

		// handle possible upload
		if (!empty($post['logo'])) {
			$data['logo'] = $post['logo'];
		}

		// check if adding or updating
		$where = sprintf('created_by = %d', $post['created_by']);
		return $this->_db->update('theme', $data, $where);
    }

    /**
     * Retrieve a theme by a particular subdomain.
     *
     * @access  public
     */
    public function getBySubdomain($subdomain)
    {
        $sql = sprintf('SELECT theme.*, users.dynamic_phone_tracking, users.default_phone
                        FROM users
                        INNER JOIN theme ON (users.id = theme.created_by)
                        WHERE users.subdomain = %s',
                        $this->_db->quote($subdomain));

        return $this->_db->query($sql)->fetch();
    }

    /**
     * Retrieve a theme by a particular cname.
     *
     * @access  public
     */
	public function getByCname($cname)
	{
        $sql = sprintf('SELECT theme.*, users.dynamic_phone_tracking, users.default_phone
                        FROM users
                        INNER JOIN theme ON (users.id = theme.created_by)
                        WHERE users.cname = %s',
                        $this->_db->quote($cname));

        return $this->_db->query($sql)->fetch();
	}

    /**
     * Validation rules for ats feed creation and update.
     *
     * @access  public
     * @param   array   $data
     * @return  bool
     * @throws  Skookum_Form_Validator_Exception
     */
    public function validate(array $data = array())
    {
        $validator = $this->getValidator($data);

		// ensure we have a creator
        $validator
            ->required('You must be logged in to update a theme.')
			->integer('Your User ID appears to be invalid.')
            ->validate('created_by', 'Created By');

		// ensure we have a feed type
        $validator
            ->required('You must enter a company display name.')
            ->validate('company', 'Company');

		$validator
			->url('website', 'Website')
			->validate('website', 'Website');

        $validator
            ->required('You must enter a background color.')
            ->validate('bgcolor', 'bgcolor');

        $validator
            ->required('You must enter a foreground color.')
            ->validate('fgcolor', 'fgcolor');

        $validator
            ->required('You must enter a link color.')
            ->validate('link', 'link');

        $validator
            ->required('You must enter a link hover color.')
            ->validate('link_hover', 'link_hover');

        $validator
            ->required('You must enter a button background color.')
            ->validate('bgbutton', 'bgbutton');

		$validator
			->required('You must enter a button background hover color.')
			->validate('bgbutton_hover', 'bgbutton_hover');

        $validator
            ->required('You must enter a button foreground color.')
            ->validate('fgbutton', 'fgbutton');

        $validator
            ->required('You must enter a heading color.')
            ->validate('heading', 'heading');

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
