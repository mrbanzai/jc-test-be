<?php
class Admin_JobsController extends Auth_Controller_Action
{
    // load some models
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

		// load the job model
		$this->Ats_Job = new Ats_Job();

		$this->layout = Zend_Layout::getMvcInstance();
	}

	/**
	 * Job listings. Display depends on the user role. Admins and above
	 * can see all jobs whereas users can only see jobs associated to
	 * their account.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
		// if we're an admin, grab all jobs
		if (in_array($this->view->user->role, array(Users::ROLE_ADMIN, Users::ROLE_SUPER))) {
			$this->view->jobs = $this->Ats_Job->getAll();
		} else {
			$this->view->jobs = $this->Ats_Job->getAllByUserId($this->view->user->id);
		}

		$this->view->title = 'Job Listings';
	}

	/**
	 * Create a new job.
	 *
	 * @access	public
	 * @return	void
	 */
	public function createAction()
	{
		// set view params
		$this->view->title = "Create a New Job";
		$this->view->edit = false;

		// offload all of the work
		$this->_update();
	}

	/**
	 * Edit an existing job.
	 *
	 * @access	public
	 * @return	void
	 */
	public function editAction()
	{
		// get the job id
		$job_id = isset($this->_params['id']) ? $this->_params['id'] : null;

		// set view params
		$this->view->title = "Edit Job";
		$this->view->edit = true;

		// offload all of the work
		$this->_update($job_id);
	}

	/**
	 * Allows for toggling of a job's "closed" status.
	 *
	 * @access	public
	 * @return	void
	 */
	public function closeAction()
	{
		// turn off view and layout
		$this->disableRender();

		if (empty($this->_params['id']) || !is_numeric($this->_params['id'])) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a valid job.'));
			die('error');
		}

		if (!isset($this->_params['status'])) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a closed status.'));
			die('error');
		}

		// a map of user roles to the changeable roles
		$roleMap = array(
			Users::ROLE_ADMIN => array(Users::ROLE_USER => 'Client'),
			Users::ROLE_SUPER => array(Users::ROLE_USER => 'Client', Users::ROLE_ADMIN => 'Admin')
		);

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// the job status
		$status = $this->_params['status'];

		// the job to delete
		$delete_job_id = (int) $this->_params['id'];

		// administrators can create jobs on behalf of other people
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
		if ($isAdmin) {
			$delete_job = $this->Ats_Job->getById($delete_job_id);
		} else {
			$delete_job = $this->Ats_Job->getByIdSecure($delete_job_id, $user_id);
		}

		// if we had trouble finding the job
		if (!$delete_job) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a valid job.'));
			die('error');
		}

		// attempt to delete
		if ($this->Ats_Job->setStatus($delete_job_id, $status)) {
			die('success');
		} else {
			die('error');
		}
	}

    /**
     * Enable deletions of client jobs if the user has proper access.
     *
     * @access  public
     * @return  void
     */
	public function deleteAction()
	{
		if (empty($this->_params['id']) || !is_numeric($this->_params['id'])) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a valid job to delete.'));
			$this->_redirect('/admin/jobs/');
		}

		// a map of user roles to the changeable roles
		$roleMap = array(
			Users::ROLE_ADMIN => array(Users::ROLE_USER => 'Client'),
			Users::ROLE_SUPER => array(Users::ROLE_USER => 'Client', Users::ROLE_ADMIN => 'Admin')
		);

        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// the job to delete
		$delete_job_id = (int) $this->_params['id'];

		// administrators can create jobs on behalf of other people
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
		if ($isAdmin) {
			$delete_job = $this->Ats_Job->getById($delete_job_id);
		} else {
			$delete_job = $this->Ats_Job->getByIdSecure($delete_job_id, $user_id);
		}

		// if we had trouble finding the job
		if (!$delete_job) {
			$this->_helper->FlashMessenger(array('error' => 'You must specify a valid job to delete.'));
			$this->_redirect('/admin/jobs/');
		}

		// attempt to delete
		if ($this->Ats_Job->delete($delete_job_id)) {
			$this->_helper->FlashMessenger(array('success' => 'You have successfully deleted the job "' . Clean::xss($delete_job['name']) . '".'));
		} else {
			$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to delete the job "' . Clean::xss($delete_job['name']) . '".'));
		}

		// redirect back
		$this->_redirect('/admin/jobs/');
	}

	/**
	 * Wrapper for both add and edit job.
	 *
	 * @access	private
	 * @param	mixed	$job_id
	 */
	private function _update($job_id = null)
	{
        // load the logged in user data
		$user = (array) $this->getUser();
		$user_id = (int) $user['id'];
		$role_id = (int) $user['role'];

		// administrators can create jobs on behalf of other people
		$isAdmin = in_array($role_id, array(Users::ROLE_ADMIN, Users::ROLE_SUPER));
		if ($isAdmin) {
			// retrieve all users with "Client" role
			$Users = new Users();
			$this->view->clients = $Users->getAllUsersByRole(Users::ROLE_USER);

			// determine if editing a job
			if (!is_null($job_id)) {
				// load the job
				$job = $this->Ats_Job->getById($job_id);
				if (!$job) {
					// add an error message
					$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to retrieve the existing job data.'));
					$this->_redirect('/admin/jobs/index/');
				}
			}

		// clients can only edit their own jobs
		} else if (!is_null($job_id)) {
			// retrieve the original job by it's id
			$job = $this->Ats_Job->getByIdSecure($job_id, $user_id);
			if (!$job) {
				// add an error message
				$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to retrieve the existing job data. Either the job does not exist or isn\'t editable.'));
				$this->_redirect('/admin/jobs/index/');
			}
		}

        // handle post requests (add/edit)
        if ($this->getRequest()->isPost()) {
			$this->_handlePostRequest($user, $isAdmin);

			if (!isset($job)) {
				$job = array(
					'id' => null,
					'created_by' => null,
					'feed_id' => null,
					'job_id' => null,
					'uristub' => null,
					'company' => null,
					'name' => null,
					'location' => null,
					'category' => null,
					'department' => null,
					'schedule' => null,
					'shift' => null,
					'description' => null,
					'qualifications' => null,
					'num_openings' => null,
					'years_exp' => null,
					'job_url' => null,
					'apply_url' => null,
					'apply_phone' => null,
					'tracking_code' => null,
                    'outbound_link_url' => null,
                    'modal_style' => null,
					'editable' => 1,
					'closed' => 0,
					'deleted' => 0,
					'date_posted' => null
				);
			}

			$job = array_merge($job, array_intersect_key($_POST, $job));
		}

		// view data
		$this->view->isAdmin = $isAdmin;
		$this->view->job = isset($job) ? $job : null;

		// set the view renderer
		$this->_helper->viewRenderer('update');
	}

	/**
	 * Handles POST requests for add/edit job.
	 *
	 * @access	private
	 * @param	array	$user
	 * @param	bool	$isAdmin
	 */
	private function _handlePostRequest($user, $isAdmin)
	{
		// the post data
		$post = $this->getRequest()->getPost();

		// ensuring we submit on behalf of the appropriate user
		$post['created_by'] = !$isAdmin ? $user['id'] : $post['created_by'];

		// ensure valid CSRF token
		if ($this->isCsrfTokenValid()) {

			try {

				// determine if we are adding or editing
				$job_id = isset($post['id']) ? (int) $post['id'] : null;
				if (!empty($job_id)) {
					$this->_handleEditRequest($job_id, $post, $user, $isAdmin);
				} else {
					$this->_handleAddRequest($post);
				}

			} catch (Skookum_Form_Validator_Exception $e) {
				$this->view->message = 'An error occurred attempting to submit your job data.';
				$this->_handleFormError($e, 'updateJob');
			}

		} else {
			$this->_helper->FlashMessenger(array('error' => 'Your session token has expired. Please try again.'));
		}
	}

	/**
	 * Handles add job requests.
	 *
	 * @access	public
	 * @param	array	$post
	 */
	private function _handleAddRequest($post)
	{
		// attempt to add the job
		$job_id = $this->Ats_Job->addEdit($post);
		if ($job_id) {
			// add search index
			$Search = new Search();
			$Search->indexJob($this->Ats_Job->getById($job_id));
			
			// add a success message
			$this->_helper->FlashMessenger(array('success' => 'Your new job has successfully been created.'));
			$this->_redirect('/admin/jobs/index/');
		} else {
			// add an error message
			$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to create the new job.'));
		}
	}

	/**
	 * Handles edit job requests.
	 *
	 * @access	public
	 * @param	int		$job_id
	 * @param	array	$post
	 * @param	array	$user
	 * @param	bool	$isAdmin
	 */
	private function _handleEditRequest($job_id, $post, $user, $isAdmin)
	{
		// retrieve the original job by it's id
		$job = $this->Ats_Job->getById($job_id);
		if (!$job) {
			// add an error message
			$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to retrieve the existing job data.'));
		}

		// ensure the job is editable
		if (!$isAdmin && $job['editable'] == 0) {
			$this->_helper->FlashMessenger(array('error' => 'The job you are attempting to edit is not editable.'));
			$this->_redirect('/admin/jobs/create/');
		}

		// ensure non-admins aren't posting a job they don't have access to
		if (!$isAdmin && $user['id'] != $job['created_by']) {
			$this->_helper->FlashMessenger(array('error' => 'You do not have permission to modify job you are attempting to edit.'));
			$this->_redirect('/admin/jobs/create/');
		}

		// perform the update
		if ($this->Ats_Job->addEdit($post)) {
			// update search index
			$Search = new Search();
			$Search->updateJob($this->Ats_Job->getById($job_id));

			// add a success message
			$this->_helper->FlashMessenger(array('success' => 'The job has successfully been updated.'));
			$this->_redirect('/admin/jobs/index/');
		} else {
			// add an error message
			$this->_helper->FlashMessenger(array('error' => 'An error occurred attempting to update the job.'));
		}
	}

}