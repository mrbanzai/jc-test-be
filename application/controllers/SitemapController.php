<?php
/**
 * Used to display a dynamic sitemap for the particular client.
 */
class SitemapController extends Skookum_Controller_Action
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
		$this->Sitemap = new Sitemap();

        // disable the layout
        $this->disableLayout();
        $this->disableRender();
	}

    /**
     * Default action.
     *
     * @access  public
     * @return  void
     */
    public function indexAction()
    {
        // output the css file
        header('Content-type: text/xml');

        $sitemap = $this->Sitemap->get($this->_getSubdomain());
        if (!empty($sitemap)) {
            echo $sitemap;
        }
    }

}