<?php
class CssController extends Skookum_Controller_Action
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
		$this->Theme = new Theme();

        // disable the layout
        $this->view->layout()->disableLayout();
	}

    /**
     * Default action.
     *
     * @access  public
     * @return  void
     */
    public function themeAction()
    {
        // output the css file
        header('Content-type: text/css');
    }

}