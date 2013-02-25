<?php
class StateController extends Skookum_Controller_Action
{

	// store the search model
    protected $Search;
	protected $State;
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
		$this->State = new State();
        $this->Location = new Location();
		$this->Category = new Category();
		$this->Ats_Job = new Ats_Job();
	}

	/**
	 * Default location page. Handles scenarios of both showing
	 * all locations as well as jobs for a particular location.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
        $state = !empty($this->_params['state']) ? $this->_params['state'] : NULL;
        if (!empty($city)) {
            // we have a particular location in mind, find all jobs
			$this->view->results = $this->Search->jobs($this->_getSubdomain(), NULL, $state);
        } else {
            // no location in mind, show all locations
            $this->view->states = $this->State->getAllBySubdomain($this->_getSubdomain());
        }

		$this->view->title = 'All States';

		$this->view->state = $state;
		$this->view->dropdownCategories = $this->Category->getAllForDropdown($this->_getSubdomain());
		$this->view->dropdownLocations = $this->Location->getAllForDropdown($this->_getSubdomain(), $state);

		// popular categories
		$this->view->categories = $this->Category->getMostPopular(8, $this->_getSubdomain());
	}

}
