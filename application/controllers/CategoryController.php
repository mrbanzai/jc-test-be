<?php
class CategoryController extends Skookum_Controller_Action
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
	}

	/**
	 * Default category page. Handles scenarios of both showing
	 * all categories as well as jobs for a particular category.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
        $category = !empty($this->_params['category']) ? $this->_params['category'] : NULL;
        if (!empty($category)) {
            // we have a particular category in mind, find all jobs
			$this->view->results = $this->Search->jobs($this->_getSubdomain(), NULL, NULL, $category);

			// set the title and description
			$clean = Clean::xss($category);
			$this->view->title = $clean;
			$this->view->description = 'Check out all of the job openings for ' . $clean . '.';
        } else {
            // no category in mind, show all categories
            $this->view->categories = $this->Category->getAllBySubdomain($this->_getSubdomain());

			// set the title
			$this->view->title = 'All Categories';
        }

		$this->view->category = $category;
		$this->view->dropdownCategories = $this->Category->getAllForDropdown($this->_getSubdomain(), $category);
		$this->view->dropdownLocations = $this->Location->getAllForDropdown($this->_getSubdomain());

		// popular locations
		$this->view->locations = $this->Location->getMostPopular(8, $this->_getSubdomain());
	}

}
