<?php
class IndexController extends Skookum_Controller_Action
{

    /**
     * The base level init handler.
     *
     * @access  public
     * @return  void
     */
    public function init()
    {
        parent::init();

		// load models
		$this->Location = new Location();
		$this->Category = new Category();
		$this->Ats_Job = new Ats_Job();

		$this->setLayout('home');
	}

	/**
	 * Default landing page.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
    {
		$this->view->categories = $this->Category->getMostPopular(8, $this->_getSubdomain());
		$this->view->locations = $this->Location->getMostPopular(8, $this->_getSubdomain());
		$this->view->recent = $this->Ats_Job->getMostRecent(8, $this->_getSubdomain());
		$this->view->dropdownCategories = $this->Category->getAllForDropdown($this->_getSubdomain());
		$this->view->dropdownLocations = $this->Location->getAllForDropdown($this->_getSubdomain());
    }

}
