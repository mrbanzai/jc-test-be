<?php
class SearchController extends Skookum_Controller_Action
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
		$this->setLayout('search');

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
	 * Default job search page.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
		$request = $this->getRequest();
		if ($request->isPost()) {

			// get post data
			$post = $request->getPost();

			$search = !empty($post['search']) ? $post['search'] : null;
			if (!empty($search)) {
				$this->view->title = 'Search results for "' . Clean::xss($search) . '"';
			}

			$location = !empty($post['location']) ? $post['location'] : null;
			$category = !empty($post['category']) ? $post['category'] : null;

			$this->view->location = $location;
			$this->view->category = $category;

			// validate the post
			if ($this->isCsrfTokenValid()) {

				// search for matching jobs based on a number of criteria
				$this->view->results = $this->Search->jobs($this->_getSubdomain(), $search, $location, $category);
				$this->view->searchterm = $search;

				// check for matching jobs in a given category or location
				$this->view->jobsInCategory = !empty($category) ? $this->Search->jobs($this->_getSubdomain(), NULL, NULL, $category, 5) : NULL;
				$this->view->jobsInLocation = !empty($location) ? $this->Search->jobs($this->_getSubdomain(), NULL, $location, NULL, 5) : NULL;

			} else {
				$this->_helper->FlashMessenger(array('error' => 'Your session token has expired. Please try again.'));
			}

		}

		$this->view->dropdownCategories = $this->Category->getAllForDropdown($this->_getSubdomain(), isset($category) ? $category : NULL);
		$this->view->dropdownLocations = $this->Location->getAllForDropdown($this->_getSubdomain(), isset($location) ? $location : NULL);
	}

    /**
     * Search for jobs matching given restraints.
     *
     * @access  public
     * @return  void
     */
    public function jobAction()
    {
        $context = $this->_helper->ajaxContext()->getCurrentContext();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->view->status = "error";
            if ($this->isCsrfTokenValid()) {

                // get the search data
                $post = $request->getPost();

                // search for matching jobs
                $this->view->categories = $this->Search->job($this->_getSubdomain(), $post['value']);
                $this->view->status = "success";

            } else {
                $this->view->status = "error";
            }
        }

        // redirect if not using the json context
        if ($context != 'json') {
            $this->_redirect('/');
        }
    }

    /**
     * Job category autocompletion.
     *
     * @access  public
     * @return  void
     */
    public function categoryAction()
    {
        $context = $this->_helper->ajaxContext()->getCurrentContext();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->view->status = "error";
            if ($this->isCsrfTokenValid()) {

                // get the search data
                $post = $request->getPost();

                // search for matching categories
                $this->view->categories = $this->Search->category($post['value'], $this->_getSubdomain());
                $this->view->status = "success";

            } else {
                $this->view->status = "error";
            }
        } else {
            $this->view->status = 'error';
        }

        // redirect if not using the json context
        if ($context != 'json') {
            $this->_redirect('/');
        }
    }

    /**
     * Job location autocompletion.
     *
     * @access  public
     * @return  void
     */
    public function locationAction()
    {
        $context = $this->_helper->ajaxContext()->getCurrentContext();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->view->status = "error";
            if ($this->isCsrfTokenValid()) {

                // get the search data
                $post = $request->getPost();

                // search for matching categories
                $this->view->locations = $this->Search->location($post['value'], $this->_getSubdomain());
                $this->view->status = "success";

            } else {
                $this->view->status = "error";
            }
        }

        // redirect if not using the json context
        if ($context != 'json') {
            $this->_redirect('/');
        }
    }

}