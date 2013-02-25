<?php
class Api_JobsController extends Skookum_Api_Server
{

    /**
     * A list of the allowed actions for this API controller.
     *
     * @var array
     */
	protected $_action_map = array(
        'GET' => array('index', 'id', 'count', 'titles', 'radius', 'search', 'uristub')
    );

	/**
	 * Specify required parameters for a particular request action. The sub-array
	 * of parameters is stored as PARAM => VALIDATOR where the validator can
	 * also ensure the data is of the proper type.
	 */
	protected $validators = array(
		'id' => array(
            'id' => array('required', 'numeric')
        ),
        'radius' => array(
            'zipcode' => array(
                'required',
                'minlength' => 5,
                'maxlength' => 11
            )
        )
	);

    /**
     * Model(s).
     * @var object
     */
    protected $_job;

    /**
     * Load models.
     *
     * @access  public
     * @return  void
     */
    public function init()
    {
        parent::init();

        // load the model(s)
        $this->_job = new Api_Job($this->_global['websiteUrl']);
    }

    /**
     * Retrieval of all jobs.
     *
     * @access  public
     * @return  void
     */
    public function indexAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // check for pagination
            $page = !empty($this->params['page']) ? (int) $this->params['page'] : 0;
            $perPage = !empty($this->params['perPage']) ? (int) $this->params['perPage'] : 10;
            if (!in_array($perPage, array(1, 2, 3, 4, 5, 10, 25, 50, 100, 250, 500))) {
                $perPage = 25;
            }
            $orderBy = !empty($this->params['orderBy']) ? strtolower($this->params['orderBy']) : 'title';
            if (!in_array($orderBy, array('title', 'category', 'department', 'city', 'state', 'location', 'name', 'last_modified', 'date_posted'))) {
                $orderBy = 'title';
            }
            $sortOrder = !empty($this->params['sortOrder']) ? strtoupper($this->params['sortOrder']) : 'ASC';
            if (!in_array($sortOrder, array('ASC', 'DESC'))) {
                $sortOrder = 'ASC';
            }

            // retrieve with sorting
            $response['data'] = $this->_job->getAllByUserId(
                $this->user,
                $page,
                $perPage,
                $orderBy,
                $sortOrder
            );
        }

		// return the data
		$this->sendFile($response, 'jobs.json');
    }

    /**
     * Retrieval of a specific job by id.
     *
     * @access  public
     * @return  void
     */
    public function idAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // retrieve with sorting
            $response['data'] = $this->_job->getById(
                $this->params['id'],
                $this->user
            );
        }

		// return the data
		$this->sendFile($response, 'jobs_id.json');
    }

    /**
     * Retrieval of a specific job by uristub.
     *
     * @access  public
     * @return  void
     */
    public function uristubAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // retrieve with sorting
            $response['data'] = $this->_job->getByUriStub(
                $this->params['uristub'],
                $this->user
            );
        }

		// return the data
		$this->sendFile($response, 'jobs_uristub.json');
    }

    /**
     * Retrieval of all job titles.
     *
     * @access  public
     * @return  void
     */
    public function titlesAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // check for pagination
            $page = !empty($this->params['page']) ? (int) $this->params['page'] : 0;
            $perPage = !empty($this->params['perPage']) ? (int) $this->params['perPage'] : 10;
            if (!in_array($perPage, array(1, 2, 3, 4, 5, 10, 25, 50, 100, 250, 500))) {
                $perPage = 25;
            }
            $sortOrder = !empty($this->params['sortOrder']) ? strtoupper($this->params['sortOrder']) : 'ASC';
            if (!in_array($sortOrder, array('ASC', 'DESC'))) {
                $sortOrder = 'ASC';
            }

            // retrieve with sorting
            $response['data'] = $this->_job->getTitlesByUserId(
                $this->user,
                $page,
                $perPage,
                $sortOrder
            );
        }

		// return the data
		$this->sendFile($response, 'jobs_titles.json');
    }

    /**
     * Find jobs within a given radius of a zip code.
     *
     * @access  public
     * @return  void
     */
    public function radiusAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // check for radius
            $radius = !empty($this->params['radius']) ? (int) $this->params['radius'] : 10;
            if (!in_array($radius, array(5, 10, 25, 50, 75, 100, 250, 500))) {
                $radius = 10;
            }
            // check for pagination
            $page = !empty($this->params['page']) ? (int) $this->params['page'] : 0;
            $perPage = !empty($this->params['perPage']) ? (int) $this->params['perPage'] : 10;
            if (!in_array($perPage, array(1, 2, 3, 4, 5, 10, 25, 50, 100, 250, 500))) {
                $perPage = 25;
            }
            $orderBy = !empty($this->params['orderBy']) ? strtolower($this->params['orderBy']) : 'distance';
            if (!in_array($orderBy, array('title', 'distance'))) {
                $orderBy = 'distance';
            }
            $sortOrder = !empty($this->params['sortOrder']) ? strtoupper($this->params['sortOrder']) : 'ASC';
            if (!in_array($sortOrder, array('ASC', 'DESC'))) {
                $sortOrder = 'ASC';
            }

            // retrieve with sorting
            $response['data'] = $this->_job->getJobsWithinRadius(
                $this->user,
                $this->params['zipcode'],
                $radius,
                $page,
                $perPage,
                $orderBy,
                $sortOrder
            );
        }

		// return the data
		$this->sendFile($response, 'jobs_titles.json');
    }

    /**
     * Retrieval of the total number of available jobs.
     *
     * @access  public
     * @return  void
     */
    public function countAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // check for params
            $city = !empty($this->params['city']) ? $this->params['city'] : null;
            $state = !empty($this->params['state']) ? $this->params['state'] : null;
            $location = !empty($this->params['location']) ? $this->params['location'] : null;
            $category = !empty($this->params['category']) ? $this->params['category'] : null;
            $schedule = !empty($this->params['schedule']) ? $this->params['schedule'] : null;

            // retrieve with sorting
            $response['data'] = $this->_job->getCountByUserId(
                $this->user,
                $city,
                $state,
                $location,
                $category,
                $schedule
            );
        }

		// return the data
		$this->sendFile($response, 'jobs_count.json');
    }

    /**
     * Search for specific jobs based on filter criteria.
     *
     * @access  public
     * @return  void
     */
    public function searchAction()
    {
        // initially set response
        $response = array(
            'status_code'   => $this->status_code,
            'data'          => array()
        );

		// validate incoming data
		if (!$this->hasValidParameters()) {
			// missing data, set the status code accordingly
			$response['status_code'] = $this->status_code = Skookum_Api_Server::STATUS_BADREQUEST;
		} else {
            // check for pagination
            $page = !empty($this->params['page']) ? (int) $this->params['page'] : 0;
            $perPage = !empty($this->params['perPage']) ? (int) $this->params['perPage'] : 10;
            if (!in_array($perPage, array(1, 2, 3, 4, 5, 10, 25, 50, 100, 250, 500))) {
                $perPage = 25;
            }
            $orderBy = !empty($this->params['orderBy']) ? strtolower($this->params['orderBy']) : 'distance';
            if (!in_array($orderBy, array('title', 'category', 'department', 'city', 'state', 'location' , 'name', 'last_modified', 'date_posted'))) {
                $orderBy = 'title';
            }
            $sortOrder = !empty($this->params['sortOrder']) ? strtoupper($this->params['sortOrder']) : 'ASC';
            if (!in_array($sortOrder, array('ASC', 'DESC'))) {
                $sortOrder = 'ASC';
            }

            $job_id = !empty($this->params['job_id']) ? $this->params['job_id'] : null;
            // check for params
            $city = !empty($this->params['city']) ? $this->params['city'] : null;
            $state = !empty($this->params['state']) ? $this->params['state'] : null;
            $location = !empty($this->params['location']) ? $this->params['location'] : null;
            $category = !empty($this->params['category']) ? $this->params['category'] : null;
            $schedule = !empty($this->params['schedule']) ? $this->params['schedule'] : null;
            $name = !empty($this->params['name']) ? $this->params['name'] : null;

            if (!$job_id) {
                // retrieve with sorting
                $response['data'] = $this->_job->searchByUserId(
                    $this->user,
                    $city,
                    $state,
                    $location,
                    $category,
                    $schedule,
                    $name,
                    $page,
                    $perPage,
                    $orderBy,
                    $sortOrder
                );
            } else {
                $job = $this->_job->getByJobId($job_id, $this->user);

                // Mock up search response for a particular job
                $response['data'] = array(
                    'results' => array($job),
                    'url' => $this->_global['websiteUrl'] . 'jobs/all/',
                    'city' => $city,
                    'state' => $state,
                    'location' => $location,
                    'category' => $category,
                    'schedule' => $schedule,
                    'name' => $name,
                    'page' => 1,
                    'perPage' => $perPage,
                    'orderBy' => $orderBy,
                    'sortOrder' => $sortOrder,
                    'nextPage' => null,
                    'numResults' => 1,
                    'totalResults' => $job ? 1 : 0,
                    'totalPages' => 1
                );
            }

        }
		// return the data
		$this->sendFile($response, 'jobs_search.json');
    }

}
