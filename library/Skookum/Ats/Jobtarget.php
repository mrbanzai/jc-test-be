<?php
/**
 * Base ATS class implementing the interface.
 */
class Skookum_Ats_Jobtarget
    extends Skookum_Ats
    implements Skookum_Ats_Interface {

    /**
     * QueryPath object.
     * @var QueryPath
     */
    protected $_qp;

    protected $_categoryMap = array(
            'Accounting/Finance' => 'Accounting/Finance/Purchasing',
            'Admin/Clerical/Secretarial' => 'Administration',
            'Customer Service' => 'Student Services',
            'Executive/Management' => 'Academic Affairs',
            'Marketing' => 'Enrollment/Marketing',
        );

    /**
     * The default constructor. Uses dependency injection to load any
     * requirements set forth by the interface.
     *
     * @access  public
     * @param   QueryPath   $qp
     */
    public function __construct(QueryPath $qp)
    {
        // store the query path object
        $this->_qp = $qp;
    }

    /**
     * Scrape the job listings page.
     *
     * @access  public
     * @param   array   $jobList
     * @param   string  $jobListType
     * @param   int     $page
     * @return  array
     */
    public function scrapeJobListings($jobList, $jobListType = 'XML', $page = 1)
    {
        // for storing matches
        $matches = array();

        // grab the content
        try {

            // make a cURL request
            $data = $this->request($jobList['url'], null, 60);
            if ($data) {
                // grab options based on the job list type
                $options = $this->getOptionsByType($jobListType);

                // load the data into QueryPath
                libxml_use_internal_errors(TRUE);
                $this->_qp->load($data, $options);
                libxml_clear_errors();

                // find data matches (job detail urls)
                $results = $this->_qp->find('source job');
                foreach ($results as $r) {

                    // retrieve category from the job title (ew..)
                    $title = $r->branch()->find('title')->textImplode();

                    if (!($category = trim($r->branch()->find('category')->textImplode()))) {
                        /*
                        $strPosRev = strrpos($title, '-');
                        if ($strPosRev !== FALSE) {
                            $category = trim(substr($title, $strPosRev + 1));
                        } else {
                        */
                            $category = null;
                        /*
                        }
                        */
                    }

                    if (in_array($category, array_keys($this->_categoryMap))) {
                        $category = $this->_categoryMap[$category];
                    }

                    // fix the location data
                    $city = ucwords(strtolower($r->branch()->find('city')->textImplode()));
                    $state = strtoupper($r->branch()->find('state')->textImplode());
                    $state = $this->fixStates($state);
                    $location = $city;
                    $location .= ', ' . $state;

                    // handle job details for insert
                    $details = array(
                        'name' => $title,
                        //'job_id' => $r->branch()->find('referencenumber')->textImplode(),
                        'company' => $r->branch()->find('company')->textImplode(),
                        'location' => $location,
                        'city' => $city,
                        'state' => $state,
                        'zipcode' => $r->branch()->find('postalcode')->textImplode(),
                        'category' => $category,
                        'schedule' => $r->branch()->find('jobtype')->textImplode(),
                        'description' => $r->branch()->find('description')->textImplode(),
                        'apply_url' => $r->branch()->find('url')->textImplode(),
                        'years_exp' => $r->branch()->find('experience')->textImplode(),
                        'job_url' => $r->branch()->find('url')->textImplode(),
                        'date_posted' => $r->branch()->find('date')->textImplode()
                    );

                    if (preg_match('#Job ID:[^\d]*(\d+)#i', $details['description'], $jobIDMatches)) {
                        $details['job_id'] = $jobIDMatches[1];
                    }

                    array_push($matches, $details);
                }
            }

        } catch (Exception $e) {
            error_log($e);
            throw new Exception($e);
        }

        return $matches;
    }

    /**
     * Scrape a job details page.
     *
     * @access  public
     * @param   string  $jobDetailsUrl
     * @param   string  $referrerUrl
     * @param   string  $jobDetailsType
     */
    public function scrapeJobDetails($jobDetailsUrl, $referrerUrl, $jobDetailsType = 'HTML')
    {

    }

    /**
     * Scrapes the job listings page for pagination details.
     *
     * @access  public
     * @return  mixed
     */
    public function getPaginationDetails($data, $curPage = 1)
    {
        return array('curPage' => 1, 'totalPages' => 1);
    }

    /**
     * Overriding cleanse.
     *
     * @access  public
     * @param   string  $data
     * @return  string
     */
    public function cleanse($data)
    {
        $data = preg_replace('/\sstyle="(.*)"/i', '', $data);
        $data = parent::cleanse($data);
        $data = str_replace(
            array('<i></i>', '<b><i></i></b>'),
            '',
            $data
        );

        return $data;
        //return Clean::tidyUp($data, array('Tidy', 'Dom'));
    }
}
