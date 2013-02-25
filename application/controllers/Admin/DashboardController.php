<?php
class Admin_DashboardController extends Auth_Controller_Action
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

		$this->layout = Zend_Layout::getMvcInstance();
	}

    /**
     * Default action.
     *
     * @access  public
     * @return  void
     */
    public function indexAction() { }

}