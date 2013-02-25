<?php
class AdminController extends Auth_Controller_Action
{

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
	 * Default admin view.
	 *
	 * View handles all output.
	 */
	public function indexAction()
    {
		// if logged in, send to home
		if (!empty($this->view->user)) {
			$this->_redirect('/admin/dashboard/');
		}

		$this->disableLayout();
    }

}
