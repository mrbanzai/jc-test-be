<?php
class Api_CategoriesController extends Skookum_Api_Server
{

    /**
     * A list of the allowed actions for this API controller.
     *
     * @var array
     */
	protected $_action_map = array(
        'GET' => array('index', 'count')
    );

    /**
     * Model(s).
     * @var object
     */
    protected $_category;

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
        $this->_category = new Api_Category($this->_global['websiteUrl']);
    }

    /**
     * Retrieval of all categories with available jobs. Includes counts for
     * each category.
     *
     * @access  public
     * @return  void
     */
    public function indexAction()
    {
        // initially set proper status code
        $this->status_code = Skookum_Api_Server::STATUS_SUCCESS;

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
            $orderBy = !empty($this->params['orderBy']) ? strtolower($this->params['orderBy']) : 'city';
            if (!in_array($orderBy, array('category', 'count'))) {
                $orderBy = 'category';
            }
            $sortOrder = !empty($this->params['sortOrder']) ? strtoupper($this->params['sortOrder']) : 'ASC';
            if (!in_array($sortOrder, array('ASC', 'DESC'))) {
                $sortOrder = 'ASC';
            }

            // retrieve with sorting
            $response['data'] = $this->_category->getAllByUserId(
                $this->user,
                $page,
                $perPage,
                $orderBy,
                $sortOrder
            );
        }

		// return the data
		return $this->sendFile($response, 'categories.json');
    }

    /**
     * Retrieval of the total number of categories with available jobs.
     *
     * @access  public
     * @return  void
     */
    public function countAction()
    {
        // initially set proper status code
        $this->status_code = Skookum_Api_Server::STATUS_SUCCESS;

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
            $response['data'] = $this->_category->getCountByUserId($this->user);
        }

		// return the data
		return $this->sendFile($response, 'categories_count.json');
    }

}
