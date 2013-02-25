<?php
class Applicants extends Skookum_Model
{

	/**
	 * Retrieve all job applicants.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll()
	{
		return $this->_db->query('SELECT applicants.*, ats_jobs.id AS job_id,
									ats_jobs.uristub, ats_jobs.category,
									ats_jobs.location, ats_jobs.name AS job_name
									FROM applicants
									INNER JOIN ats_jobs ON (applicants.job_id = ats_jobs.id)
									ORDER BY applicants.created_ts DESC')->fetchAll();
	}

	/**
	 * Retrieves all users of a particular role.
	 *
	 * @access	public
	 * @param   int     $client_id
	 */
	public function getAllByClientId($client_id)
	{
		$sql = sprintf('SELECT applicants.*, ats_jobs.id AS job_id,
						ats_jobs.uristub, ats_jobs.category,
						ats_jobs.location, ats_jobs.name AS job_name
						FROM applicants
						INNER JOIN ats_jobs ON (applicants.job_id = ats_jobs.id)
						WHERE applicants.client_id = %d
						ORDER BY applicants.created_ts DESC',
						$client_id);

        return $this->_db->query($sql)->fetchAll();
	}

    /**
     * Create a new job applicant.
     *
     * @access  public
     * @param   array   $post
     * @return	mixed
     */
    public function create($post)
    {
        // validate the data
        $this->validate($post);

		// storage data
		$data = array(
			'job_id' 			=> $post['job_id'],
			'client_id' 		=> $post['client_id'],
            'name' 				=> $post['name'],
            'email' 			=> $post['email'],
            'previous_job_title' => $post['previous_job_title'],
            'cover_letter' 		=> !empty($post['cover_letter']) ? $post['cover_letter'] : NULL,
            'resume' 			=> !empty($post['resume']) ? $post['resume'] : NULL,
			'created_ts' 		=> time()
		);

		return $this->_db->insert('applicants', $data);
    }

    /**
     * Validation rules for ats feed creation and update.
     *
     * @access  public
     * @param   array   $data
     * @param	bool	$editable
     * @return  bool
     * @throws  Skookum_Form_Validator_Exception
     */
    public function validate(array $data = array(), $editable = FALSE)
    {
        $validator = $this->getValidator($data);

		// ensure we have a creator
        $validator
            ->required('You can only apply to valid jobs.')
			->integer('Your Job ID appears to be invalid.')
            ->validate('job_id', 'Job ID');

        $validator
            ->required('You can only apply to valid jobs.')
			->integer('Your Client ID appears to be invalid.')
            ->validate('client_id', 'Client ID');

        $validator
            ->required('You must enter your full name.')
            ->validate('name', 'Name');

        $validator
            ->required('You must enter your email address.')
            ->email('You must enter a valid email address.')
            ->validate('email', 'Email');

		// resume is only required for editable jobs
		if ($editable) {
			$validator
				->required('You must upload your resume.')
				->validate('resume', 'Resume');
		}

        // check for errors
        if ($validator->hasErrors()) {
            throw new Skookum_Form_Validator_Exception(
                'An error occurred while submitting your job application.',
                $validator->getAllErrors()
            );
        }

        return $validator->getValidData();
    }

}
