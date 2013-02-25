<?php
/**
 * Base ATS class implementing the interface.
 */
class Skookum_Ats_Icims
    extends Skookum_Ats
    implements Skookum_Ats_Interface {

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
        $return = array();

        // ICIMS requires url modification
        $url = parse_url($jobList['url']);
        $url = $url['scheme'] . '://' . $url['host'] . (!empty($url['port']) ? ':' . $url['port'] : '') . '/jobs/search?ss=1&searchLocation=&searchCategory=';

        // if we are requesting a child page
        if ($page > 1) {
            $url .= '&pr=' . $page;
        }

        // grab the content
        try {
            // make a cURL request
            $data = $this->request($url);
        } catch (Exception $e) {
            $data = false;
            error_log($e);
        }

        if ($data) {

            // attempt to clean malformed html
            $data = clean::tidyUpModuleTidy($data);

            // clean up the HTML beforehand
            $data = preg_replace("/[\r\n]+/s", "\n", $data);

            // match the table containing pertinent job listings
            $matches = array();
            if (preg_match('/<!-- Job Listings Table \(Nested Table\) -->(.*?)<!-- End Job Listings Table -->/is', $data, $matches)) {
                $matches = $matches[1];

                // split until we have individual rows
                $jobs = explode('<tr>', $matches);

                // remove table
                if (isset($jobs[0]) && strpos($jobs[0], '<table') !== FALSE) {
                    array_shift($jobs);
                }

                // remove the heading
                if (isset($jobs[0]) && strpos($jobs[0], '<th') !== FALSE) {
                    array_shift($jobs);
                }

                // if we have job listings
                if (!empty($jobs)) {
                    // iterate over the jobs
                    foreach ($jobs as $job) {
                        // row contains: Job ID, Job Title, Location, Posted Date
                        $jobData = array();
                        if (preg_match_all('/<td (.*?)>(.*?)<\/td>/is', $job, $jobData)) {

                            // get only the pertinent row data
                            $jobData = $jobData[2];

                            // parse out the url
                            $url = array();
                            if (preg_match('/href="(.*?)"/is', $jobData[1], $url)) {
                                $return[] = trim($url[1]);
                            }
                        }
                    }
                }
            }

            // get the pagination details
            $pagination = $this->getPaginationDetails($data, $page);
            if ($pagination['curPage'] < $pagination['totalPages']) {
                // get jobs from the next page
                $return = array_merge($return, $this->scrapeJobListings($jobList, $jobListType, ++$pagination['curPage']));
            }

        }

        return $return;
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

                // clean up the HTML beforehand
                $data = preg_replace("/[\r\n]+/s", "\n", $data);

                // grab the job title
                $jobTitle = array();
                if (preg_match('/<td class="iCIMS_Header iCIMS_Header_JobTitle">(.*?)<\/td>/is', $data, $jobTitle)) {
                    $jobTitle = $jobTitle[1];
                    $jobTitleArray = array();
                    if (preg_match('/<h1>(.*?)<\/h1>/is', $jobTitle, $jobTitleArray)) {
                        $details['name'] = trim($jobTitleArray[1]);
                    }
                }

                // grab the apply now url
                $applyNow = array();
                if (preg_match('/<a ss="[a-zA-Z0-9]+" href="(.*?)" class="iCIMS_Anchor">Apply for this job online<\/a>/is', $data, $applyNow)) {
                    $details['apply_url'] = trim($applyNow[1]);
                }

                // grab the header table
                $headerTable = array();
                if (preg_match('/iCIMS_JobHeaderTable(.*?)<\/table>/is', $data, $headerTable)) {
                    $headerTable = $headerTable[1];

                    // grab the header titles
                    $headerTitles = array();
                    if (preg_match_all('/<th scope="row" class="iCIMS_JobHeaderField">(.*?)<\/th>/is', $headerTable, $headerTitles)) {
                        $headerTitles = $headerTitles[1];
                        foreach ($headerTitles as &$t) {
                            $t = trim(str_replace(array(':', '&nbsp;'), '', $t));
                        }

                        // grab header data
                        $headerData = array();
                        if (preg_match_all('/<td class="iCIMS_JobHeaderData">(.*?)<\/td>/is', $headerTable, $headerData)) {
                            // assume a 1-1 of header titles to data
                            $headerData = $headerData[1];

                            $len = count($headerTitles);
                            for ($i = 0; $i < $len; ++$i) {
                                $fieldName = $this->_mapField($headerTitles[$i]);
                                if ($fieldName) {
                                    // store the data with associated title
                                    if (!isset($details[$fieldName])) {
                                        $details[$fieldName] = $headerData[$i];
                                    }
                                }
                            }

                            // store the job url
                            $details['job_url'] = $jobDetailsUrl;
                        }
                    }
                }

                // grab all of the detail headers
                $detailHeaders = array();
                if (preg_match_all('/<td class="iCIMS_InfoMsg iCIMS_InfoField_Job">(.*?)<\/td>/is', $data, $detailHeaders)) {
                    $detailHeaders = $detailHeaders[1];

                    // get only pertinent header text
                    foreach ($detailHeaders as &$h) {
                        $headerText = array();
                        if (preg_match('/<strong>(.*?)<\/strong>/is', $h, $headerText)) {
                            $h = trim($headerText[1]);
                        }
                    }

                    // grab all associated header content
                    $headerContent = array();
                    if (preg_match_all('/<td class="iCIMS_InfoMsg iCIMS_InfoMsg_Job">(.*?)<\/td>/is', $data, $headerContent)) {
                        // assume a 1-1 of header naming to content
                        $headerContent = $headerContent[1];
                        $len = count($detailHeaders);
                        for ($i = 0; $i < $len; ++$i) {
                            // get the mapped header name
                            $headerName = $this->_mapHeader($detailHeaders[$i]);
                            // store data with associated header
                            if (!isset($details[$headerName])) {
                                if ($headerName == 'description_secondary' && !isset($details['description'])) {
                                    $details['description'] = '<h5>' . ucwords($detailHeaders[$i]) . '</h5>' . $this->cleanse($headerContent[$i]);
                                } else if ($headerName == 'description_secondary') {
                                    $details[$headerName] = '<h5>' . ucwords($detailHeaders[$i]) . '</h5>' . $this->cleanse($headerContent[$i]);
                                } else {
                                    $details[$headerName] = $this->cleanse($headerContent[$i]);
                                }
                            } else {
                                if ($headerName == 'description_secondary') {
                                    $details[$headerName] .= "\n\n" . '<br /><br /><h5>' . ucwords($detailHeaders[$i]) . '</h5>' . $this->cleanse($headerContent[$i]);
                                } else {
                                    $details[$headerName] .= '<br /><br />' . $this->cleanse($headerContent[$i]);
                                }
                            }
                        }

                        // merge description with description_secondary
                        if (!empty($details['description_secondary'])) {
                            $details['description'] .= $details['description_secondary'];
                            unset($details['description_secondary']);
                        }

                        // fix up some inline styles
                        if (!empty($details['description'])) {
                            $details['description'] = preg_replace('/\sstyle="(.*)"/i', '', $details['description']);
                        }

                        if (!empty($details['qualifications'])) {
                            $details['qualifications'] = preg_replace('/\sstyle="(.*)"/i', '', $details['qualifications']);
                        }

                    }
                }

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
        $return = array('curPage' => 1, 'totalPages' => 1);

        // filter down to the containing table
        $table = array();
        if (preg_match('/iCIMS_JobsTablePaging(.*?)<\/tr>/is', $data, $table)) {
            $table = $table[1];

            // filter down to the second TD
            $tds = array();
            if (preg_match_all('/<td>(.*?)<\/td>/is', $table, $tds)) {
                $tds = $tds[1];
                $td = $this->cleanse($tds[1]);

                // parse out the page numbers
                $pages = array();
                if (preg_match('/Page (\d+) of (\d+)/is', $td, $pages)) {
                    $return['curPage'] = isset($pages[1]) ? (int) $pages[1] : 1;
                    $return['totalPages'] = isset($pages[2]) ? (int) $pages[2] : 1;
                }
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