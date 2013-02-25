<?php
class JobsController extends Skookum_Controller_Action
{

	// store the search model
    protected $Search;
	protected $Location;
	protected $Category;
	protected $Ats_Job;

	/**
	 * Initialize all instance fields for needed model objects.
	 *
	 * @access	public
	 * @return	void
	 */
	public function init() {
		parent::init();

		// use the search layout
		$this->setLayout('default');

		// load models
		$this->Search = new Search();
		$this->Location = new Location();
		$this->Category = new Category();
		$this->Ats_Job = new Ats_Job();

        // ajax context switching
        $this->_helper->ajaxContext()
            ->addActionContext('job', 'json')
            ->addActionContext('category', 'json')
            ->addActionContext('location', 'json')
            ->initContext();
	}

	/**
	 * Job listing.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
        $uristub = !empty($this->_params['uristub']) ? $this->_params['uristub'] : NULL;
        $category = !empty($this->_params['category']) ? $this->_params['category'] : NULL;
        $location = !empty($this->_params['location']) ? $this->_params['location'] : NULL;
        // grab the pertinent job
        $job = $this->Ats_Job->getByUristub($uristub, $this->_getSubdomain());

		// handle title
		if (!empty($job['name'])) {
			$this->view->title = Clean::xss($job['name']);
		}

		// handle description
		if (!empty($job['description'])) {
			$this->view->description = Clean::xss($job['description']);
		}

        // check for matching jobs in a given category or location
		$this->view->job = $job;
		$this->view->dropdownCategories = $this->Category->getAllForDropdown($this->_getSubdomain(), $category);
		$this->view->dropdownLocations = $this->Location->getAllForDropdown($this->_getSubdomain(), $location);

		// obtain the "apply now" url for redirection
		$apply_now_url = NULL;
		if (!empty($job['outbound_link_url'])) {
			$apply_now_url = $job['outbound_link_url'];
		} else if (!empty($job['apply_url'])) {
			$apply_now_url = $job['apply_url'];
		} else if (!empty($job['job_url'])) {
			$apply_now_url = $job['job_url'];
		}

		// pass in whether the job is editable or not
		$editable = (isset($job['editable']) && $job['editable'] == 1) ? true : false;

		// watch for job application submissions
		$this->_handleApplyNow($apply_now_url, $editable);

		// make jobs in category available
		if (!empty($job['category'])) {
			$this->view->jobsInCategory = $this->Search->jobs(
				$this->_getSubdomain(),
				NULL,
				NULL,
				Clean::uristub($job['category']),
				5
			);
		}

		// make jobs in location available
		if (!empty($job['location'])) {
			$this->view->jobsInLocation = $this->Search->jobs(
				$this->_getSubdomain(),
				NULL,
				Clean::uristub($job['location']),
				NULL,
				5
			);
		}

		$this->view->isJobPosting = true;
	}

	/**
	 * Lists all available jobs.
	 *
	 * @access	public
	 * @return	void
	 */
	public function allAction()
	{
		// need the search layout
		$this->setLayout('search');

		$this->view->title = 'All Jobs';

		// find all results
		$this->view->results = $this->Ats_Job->getMostRecent(NULL, $this->_getSubdomain());

		// populate dropdowns
		$this->view->dropdownCategories = $this->Category->getAllForDropdown($this->_getSubdomain());
		$this->view->dropdownLocations = $this->Location->getAllForDropdown($this->_getSubdomain());
	}

	/**
	 * A user is applying for a job.
	 *
	 * @access	public
	 * @return	void
	 */
	protected function _handleApplyNow($apply_now_url = NULL, $editable = FALSE)
	{
        // populate the form if we aren't updating
        if ($this->getRequest()->isPost()) {

			// the post data
			$post = $this->getRequest()->getPost();

			// for re-populating the form on failure
			$fields = array(
				'id' => null,
				'job_id' => null,
				'client_id' => null,
				'name' => null,
				'email' => null,
				'previous_job_title' => null,
				'cover_letter' => null
			);

			$this->view->applynow = array_merge($fields, array_intersect_key($post, $fields));

			// ensure valid CSRF token
			if ($this->isCsrfTokenValid()) {

				try {

					// check the image upload
					$filepath = $this->_checkUpload();
					if ($filepath !== FALSE) {
						$post['resume'] = $filepath;
					}

					$Applicants = new Applicants();
					if ($Applicants->create($post, $editable)) {
						// determine where to redirect for non-editables
						if (!$editable && !empty($apply_now_url)) {
							$this->_redirect($apply_now_url);
						} else {
							// thank you
							$this->_helper->FlashMessenger(array('success' => 'Thank you for your submission.'));
                            $this->view->hasSuccess = true;
						}
					} else {
						$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to submit your job application. Please try again.'));
                        $this->view->hasError = true;
					}

				} catch (Skookum_Form_Validator_Exception $e) {
					$this->view->message = 'An error occurred while submitting your job application. Please try again.';
					$this->_handleFormError($e, 'formApplyNow');
                    $this->view->hasError = true;
				} catch (Exception $e) {
					$e = new Skookum_Form_Validator_Exception($e->getMessage(), $this->view->applynow);
					$this->view->message = 'An error occurred while submitting your job application. Please try again.';
					$this->_handleFormError($e, 'formApplyNow');
                    $this->view->hasError = true;
				}

			} else {
				$this->_helper->FlashMessenger(array('error' => 'Your session token has expired. Please try again.'));
				$this->view->hasError = true;
			}

		}
	}

	/**
	 * Validate a resume upload.
	 *
	 * @access	private
	 */
	private function _checkUpload()
	{
		// check if we have a logo
		if (!empty($_FILES['resume']['name'])) {
			// check for errors
			if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
				switch($_FILES['resume']['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$msg = 'The file you are attempting to upload exceeds the maximum allowable filesize.';
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$msg = 'The file you are attempting to upload exceeds the maximum allowable filesize.';
						break;
					case UPLOAD_ERR_PARTIAL:
						$msg = 'The uploaded file was only partially uploaded.';
						break;
					case UPLOAD_ERR_NO_FILE:
						$msg = 'No file was uploaded.';
						break;
					UPLOAD_ERR_NO_TMP_DIR:
						$msg = 'Your file could not be uploaded due to a missing temporary folder.';
						break;
					UPLOAD_ERR_CANT_WRITE:
						$msg = 'An error occurred attempting to write your file to disk.';
						break;
					UPLOAD_ERR_EXTENSION:
						$msg = 'The file upload was stopped for an unknown reason.';
						break;
				}
				throw new Exception($msg);
			}

			// check type
			$ext = strtolower(substr($_FILES['resume']['name'], strrpos($_FILES['resume']['name'], '.') + 1));
			if (!in_array($ext, array('doc', 'docx', 'pdf'))) {
				throw new Exception('Your resume must be formatted as a doc, docx, or pdf.');
			}

			// ensure we can see the file
			if (!is_readable($_FILES['resume']['tmp_name'])) {
				throw new Exception('We could not open your resume for validating it\'s contents.');
			}

			// create a new file
			$filename = md5(mt_rand(0, time())) . '-' . time() . '.' . $ext;
			$filepath = BASE_PATH . '/public/uploads/private/' . $filename;
			if (move_uploaded_file($_FILES['resume']['tmp_name'], $filepath)) {
				@chmod($filepath, 0755);
				return '/uploads/private/' . $filename;
			}
		}

		return false;
	}

}

