<?php
/**
 * Base ATS class implementing the interface.
 */
class Skookum_Ats_Taleo
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
     * @return  array
     */
    public function scrapeJobListings($jobList, $jobListType = 'XML')
    {
        // for storing matches
        $matches = array();

        // grab the content
        try {

            // make a cURL request
            $data = $this->request($jobList['url']);
            if ($data) {
                // grab options based on the job list type
                $options = $this->getOptionsByType($jobListType);

                // load the data into QueryPath
                $this->_qp->load($data, $options);

                // find data matches (job detail urls)
                $results = $this->_qp->find('urlset url loc');
                foreach ($results as $r) {
                    $matches[] = trim($r->innerXML());
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
        // for storing details
        $details = array();

        // grab the content
        try {

            // fix the url
            $jobDetailsUrl = html_entity_decode($jobDetailsUrl);

            // make a cURL request
            $data = $this->request($jobDetailsUrl, $referrerUrl);
            if ($data) {

                // pain in the ass to get
                $matches = array();
                if (preg_match("/api.fillList\('requisitionDescriptionInterface', 'descRequisition',(.*)?\);/i", $data, $matches)) {
                    // big match of semi-pertinent data
                    $matches = trim($matches[1]);
                    $matches = str_replace(array("['", "']"), '', $matches);
                    $matches = explode("','", $matches);

                    // now match all of the parts (hopefully indices don't change)
                    $details = array(
                        'name' => urldecode($matches[9]),
                        'job_id' => urldecode($matches[10]),
                        'location' => urldecode($matches[11]),
                        'category' => urldecode($matches[13]),
                        'schedule' => urldecode($matches[15]),
                        'description' => preg_replace('/\sSTYLE="(.*)"/i', '', urldecode($matches[17])),
                        'qualifications' => preg_replace('/\sSTYLE="(.*)"/i', '', urldecode($matches[19])),
                        'job_url' => $jobDetailsUrl
                    );

                    // fix a few
                    $details['description'] = str_replace('!*!', '', $details['description']);
                    $details['qualifications'] = str_replace('!*!', '', $details['qualifications']);
                } else {
                    file_put_contents('./jobParsingErrors.txt', $jobDetailsUrl . PHP_EOL, FILE_APPEND);
                    error_log('An error occurred attempting to parse the job: ' . $jobDetailsUrl);
                }

            } else {
                file_put_contents('./jobRequestErrors.txt', $jobDetailsUrl . PHP_EOL, FILE_APPEND);
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

}