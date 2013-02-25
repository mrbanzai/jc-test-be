<?php
/**
 * Base ATS class implementing the interface.
 */
require 'Clean.php';
class Skookum_Ats_Hrsmart
    extends Skookum_Ats
    implements Skookum_Ats_Interface {
    
    private $_extra = array();
    
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
    public function scrapeJobListings($jobList, $jobListType = 'HTML', $page = 1)
    {
        // for storing matches
        $matches = array();
        
        $url = $jobList['url'];

        // if we are requesting a child page
        $url .= '?sort_table_id=job_search_results_list_table&paginate_range=25&paginate_next=' . (($page-1) * 25);
        
        // grab the content
        try {
            print_r("Getting " . $url);
            // make a cURL request
            $data = $this->request($url);
            // replace unescaped ampersands (which cause parse errors below)
            $data = clean::tidyUpModuleTidy($data);
            if ($data) {
                // grab options based on the job list type
                $options = $this->getOptionsByType($jobListType);
                // load the data into QueryPath
                libxml_use_internal_errors(TRUE);
                $this->_qp->load($data, $options);
                libxml_clear_errors();
                // find data matches (job detail urls)
                $results = $this->_qp->find('#job_search_results_list_table tbody tr');
                foreach ($results as $r) {
                    $job_url = $r->branch()->find('td:first-child a')->attr('href');
                    if(strpos($job_url, 'http') === false) {
                        $url_parts = parse_url($url);
                        $job_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $job_url;
                    }
                    array_push($matches, $job_url);
                    $r = $r->find('td:first-child');
                    $locat = trim($r->next()->textImplode());
                    $dstring = trim($r->next()->textImplode());
                    $dept = trim($r->next()->textImplode());
                    $extra = array(
                        'location' => $locat,
                        'date_posted' => $dstring,
                        'department' => $dept
                    );
                    array_push($this->_extra, $extra);
                }
                
                // get the pagination details
                $pagination = $this->getPaginationDetails($data, $page);
                if ($pagination['curPage'] < $pagination['totalPages']) {
                    // get jobs from the next page
                    $matches = array_merge($matches, $this->scrapeJobListings($jobList, $jobListType, ++$pagination['curPage']));
                }
            }

        } catch (Exception $e) {
            error_log($e);
            throw new Exception($e);
        }

        return $matches;
    }
    
    public function getExtra() {
        return $this->_extra;
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
        // for storing details
        $details = array();

        // grab the content
        try {

            // fix the url
            $jobDetailsUrl = html_entity_decode($jobDetailsUrl);

            // make a cURL request
            $data = $this->request($jobDetailsUrl, $referrerUrl);
            if ($data) {

                // attempt to clean malformed html
                $data = clean::tidyUpModuleTidy($data);
                
                // grab options based on the job list type
                $options = $this->getOptionsByType($jobDetailsType);
                // load the data into QueryPath
                libxml_use_internal_errors(TRUE);
                $this->_qp->load($data, $options);
                libxml_clear_errors();
                
                $title = trim($this->_qp->branch()->find('#job_details_ats_requisition_title')->textImplode());
                if(empty($title)) return $details;
                                
                $parse_url = parse_url($jobDetailsUrl);
                parse_str($parse_url['query']);
                
                $details['job_id'] = (int)$reqid;
                $details['job_url'] = $jobDetailsUrl;
                $details['apply_url'] = $parse_url['scheme'] . "://" . $parse_url['host'] . "/ats/apply_online.php?requisition_id=" . $reqid . "&submit_buttons%5B%5D=";
                $details['name'] = $title;
                $details['schedule'] = $this->cleanse($this->_qp->branch()->find('#job_details_hua_job_type_id')->textImplode());
                $details['category'] = $this->cleanse($this->_qp->branch()->find('#job_details_ats_requisition_category_id')->textImplode());
                
                $education = $this->cleanse($this->_qp->branch()->find('#job_details_ats_education_level_id')->textImplode());
                $requirements = $this->cleanse($this->_qp->branch()->find('#job_details_ats_requisition_requirements')->innerHTML());
                if(!empty($education)) 
                    $requirements = "<p><strong>Education:</strong> " . $education . "</p>" . $requirements;
                $details['qualifications'] = $this->cleanse($requirements);
                // somehow using innerHTML here (instead of textImplode) causes apache to crash with a segfault when we run the query
                $details['description'] = $this->cleanse($this->_qp->branch()->find('#job_details_ats_requisition_description')->innerHTML());
                
            }

        } catch (Exception $e) {
            error_log($e);
            throw new Exception($e);
        }

        // pause between each scrape
        sleep(1);

        // return data for storage
        return $details;
    }

    /**
     * Scrapes the job listings page for pagination details.
     *
     * @access  public
     * @return  mixed
     */
    public function getPaginationDetails($data, $curPage = 1)
    {
        // return data
        $return = array('curPage' => $curPage, 'totalPages' => 1);

        // filter down to the containing table
        $table = array();
        if (preg_match('/<div class="containerPagination">(.*?)<div\ class="columnRightPagination">/is', $data, $table)) {
            $table = $table[1];
            $cur_page = array();
            if (preg_match_all('/class\=\"paginateGrayedNumbers\">(.*?)<\/div>/is', $table, $cur_page)) {
                $return['curPage'] = isset($cur_page[1][0]) ? (int) $cur_page[1][0] : 1;
            }
            
            $tds = array();
            if (preg_match_all('/class="paginateNumber">(.*?)<\/a>/is', $table, $tds)) {
                $return['totalPages'] = (int)$tds[1][count($tds[1])-1];
            }
        }

        return $return;
    }

    /**
     * Maps field names to actual database fields.
     *
     * @access  private
     * @param   string  $name
     * @return  string
     */
    private function _mapField($name)
    {
        $name = strtolower($name);
        $name = rtrim(':', $name);
        $name = trim($name);

        // mapping of header names to actual fields
        $headerMapping = array(
            'job id' => 'job_id',
            'url' => 'job_url',
            '# positions' => 'num_openings',
            '# of openings' => 'num_openings',
            '# of openings remaining' => 'num_openings',
            'location' => 'location',
            'job location' => 'location',
            'experience (years)' => 'years_exp',
            'category' => 'category',
            'department' => 'department',
            'schedule' => 'schedule',
            'type' => 'schedule',
            'shift' => 'shift',
            'company' => 'company',
            'posted date' => 'date_posted'
        );

        // error log unknown mappings
        if (!isset($headerMapping[$name])) {
            file_put_contents('./missingFields.txt', $name . PHP_EOL, FILE_APPEND);
        }

        // default to description
        return isset($headerMapping[$name]) ? $headerMapping[$name] : false;
    }

    /**
     * Maps header names to actual database fields.
     *
     * @access  private
     * @param   string  $name
     * @return  string
     */
    private function _mapHeader($name)
    {
        $name = strtolower($name);
        $name = rtrim(':', $name);
        $name = trim($name);

        // mapping of header names to actual fields
        $headerMapping = array(
            'overview' => 'description',
            'external overview' => 'description',
            'responsibilities' => 'description_secondary',
            'external responsibilities' => 'description_secondary',
            'duties and responsibilities' => 'description_secondary',
            'machines operated' => 'description_secondary',
            'qualifications' => 'qualifications',
            'external qualifications' => 'qualifications'
        );

        // error log unknown mappings
        if (!isset($headerMapping[$name])) {
            file_put_contents('./missingHeaders.txt', $name . PHP_EOL, FILE_APPEND);
        }

        // default to description
        return isset($headerMapping[$name]) ? $headerMapping[$name] : 'description_secondary';
    }
    
}