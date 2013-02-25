<?php
class Admin_ApplicantsController extends Auth_Controller_Action
{

    // load some models
	public $Applications;

	protected $layout;

	/**
	 * Initialize all instance fields for needed model objects.
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
	{
		parent::init();

		$this->layout = Zend_Layout::getMvcInstance();

        $this->Applicants = new Applicants();
	}

	/**
	 * Default applicants list view.
	 *
	 * @access  public
	 * @return  void.
	 */
	public function indexAction()
    {
        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// determine if the user should see all applicants or just those pertinent to them
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
        if ($isAdmin) {
            $this->view->applicants = $this->Applicants->getAll();
        } else {
            $this->view->applicants = $this->Applicants->getAllByClientId($user_id);
        }
    }

	/**
	 * Export all of a client (or clients) applicant data.
	 *
	 * @access	public
	 * @return	void
	 */
	public function exportAction()
	{
		// disable rendering
		$this->disableRender();

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// determine if the user should see all applicants or just those pertinent to them
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
        if ($isAdmin) {
            $applicants = $this->Applicants->getAll();
        } else {
            $applicants = $this->Applicants->getAllByClientId($user_id);
        }

		try {

			// init CSV output stream
			$fp = fopen('php://temp', 'r+');

			// generate the header
			fputcsv($fp, array('Applicant ID', 'Job ID', 'Name', 'Email', 'Applied For', 'Location', 'Job Url', 'Submitted'));

			// output applicants to CSV
			if (!empty($applicants)) {
				foreach ($applicants as $a) {
					// generate the job url
					$url = '/job/details/';
					if (!empty($a['location'])) $url .= Clean::uristub($a['location']) . '/';
					if (!empty($a['category'])) $url .= Clean::uristub($a['category']) . '/';
					$url .= Clean::uristub($a['uristub']) . '/';

					// generate row data
					$row = array(
						$a['id'],
						$a['job_id'],
						Clean::xss($a['name']),
						Clean::xss($a['email']),
						Clean::xss($a['job_name']),
						Clean::xss($a['location']),
						$url,
						date('m.d.Y H:i:s', $a['created_ts'])
					);

					fputcsv($fp, $row);
				}
			}

			// rewind the output on completion
			rewind($fp);

			// begin the output string
			$csv = '';

			// convert to a string
			while($line = stream_get_line($fp, 65535, "\n")) {
				$csv .= $line . "\n";
			}

			// close the file early
			fclose($fp);

			// send the file as an attachment
			$this->_helper->sendFile->sendData(
				$csv,
				'application/csv',
				'applicants-' . date('Y-m-d') . '.csv',
				array('disposition' => 'attachment')
			);

		} catch (Exception $e) {
			// close file pointer
			fclose($fp);
			// alert of error
			var_dump($e);
			die;
		}
	}

}