<?php

class Skookum_Ats_Silkroad extends Skookum_Ats implements Skookum_Ats_Interface {

    /**
     * QueryPath object
     * @var QueryPath
     */
    protected $_qp;

    public function __construct(QueryPath $qp) {
        $this->_qp = $qp;
    }

    /**
     * Scrape the job listings page.
     *
     * @see  Skookum_Ats_Jobtarget::scrapeJobListings
     * @access  public
     * @param   array   $jobList
     * @param   string  $jobListType
     * @param   int     $page
     * @return  array
     */
    public function scrapeJobListings($jobList, $jobListType = 'XML', $page = 1) {
        
        //die('start scrape');
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
                $results = $this->_qp->find('jobs job');
                foreach ($results as $r) {

                    // fix the location
                    $city = ucfirst(strtolower($r->branch()->find('municipality')->textImplode()));
                    $state = strtoupper($r->branch()->find('region')->textImplode());
                    $location = implode(', ', array($city, $state));

                    
                    
                    $hconfig = HTMLPurifier_Config::createDefault();
                    $hconfig->set('CSS.AllowedProperties', array());
                    $purifier = new HTMLPurifier($hconfig);
    	
                    //$dirty_html = '<div style="color:red;text-align:center;"><strong>html here</strong></div>';
    	
                    //$clean_html = $purifier->purify($dirty_html);



                    $details = array(
                        'name' => $r->branch()->find('title')->textImplode(),
                        'job_id' => $r->branch()->find('jobId')->textImplode(),
                        'company' => 'Front Range Community College',
                        'location' => $location,
                        'city' => $city,
                        'state' => $state,
                        'zipcode' => $r->branch()->find('zip')->textImplode(),
                        'category' => $r->branch()->find('category')->textImplode(),
                        'qualifications' => $this->cleanse($purifier->purify($r->branch()->find('requiredSkills')->textImplode())),
                        //'schedule' => $r->branch()->find('job_type')->textImplode(),
                        'description' => $this->cleanse($purifier->purify($r->branch()->find('jobDescription')->textImplode())),
                        'apply_url' => $r->branch()->find('applyUrl')->textImplode(),
                        'job_url' => $r->branch()->find('applyUrl')->textImplode(),
                        'date_posted' => strtotime($r->branch()->find('postingDate')->textImplode())
                    );

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
    public function scrapeJobDetails($jobDetailsUrl, $referrerUrl, $jobDetailsType = 'HTML') {

    }

}