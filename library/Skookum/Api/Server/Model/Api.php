<?php
class Skookum_Api_Server_Model_Api extends Skookum_Model
{

    /**
     * The Skookum site domain for redirecting.
     * @var string
     */
    protected $_domain = 'http://jobcastle.us/';

    /**
     * Takes in the site domain.
     *
     * @access  public
     * @param   string  $domain
     * @return  void
     */
    public function __construct($domain)
    {
        parent::__construct();

        // set the domain
        $this->_domain = $domain;
    }

    /**
     * Given a requested page and a limit, calculate the offset.
     *
     * @access  public
     * @param   int     $page
     * @param   int     $limit
     * @return  int
     */
    public function getPaginationOffset($page, $limit)
    {
        if ($page == 0) {
            return 0;
        }

        return $page * $limit;
    }

}
