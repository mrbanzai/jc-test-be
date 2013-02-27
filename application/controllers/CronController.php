<?php

// useful querypath examples
// http://api.querypath.org/docs/doc_8php_source.html

class CronController extends Zend_Controller_Action
{

    /**
     * The models.
     */
    protected $Search;
    protected $AtsJobModel;
    protected $AtsTypeModel;
    protected $AtsFeedModel;
    protected $AtsFeedTypeModel;
    protected $AtsFeedListingsModel;

    /**
     * Currently supported types of ATSes.
     * Pulled from db. IDs as key.
     */
    protected $_atsTypes = array();

    /**
     * Currently supported types of feeds.
     * Pulled from db. IDs as key.
     */
    protected $_atsFeedTypes = array();

    /**
     * The single instance of QueryPath.
     */
    protected $_qp;

    /**
     * Path to a log file.
     */
    protected $_logfile;

    /**
     * Ensures we're being hit from the CLI.
     *
     * @access  public
     */
    public function init()
    {
        parent::init();

        // full blown error reporting
        @error_reporting(E_ALL|E_STRICT);

        // disable layout
        $this->_helper->layout()->disableLayout();

        // disable view
        $this->_helper->viewRenderer->setNoRender(true);

        // access DENIED
        if (PHP_SAPI != 'cli') {
            die('Unauthorized access.');
        }

        // output the environment
        error_log('Cron environment used: ' . APPLICATION_ENV);

        // load a log file
        $this->_logfile = APPLICATION_PATH . '/data/logs/' . date('Y-m-d-His') . '.log';

        // load query path
        $this->_qp = new QueryPath_QueryPath();

        // load some models
        $this->Search = new Search($this->_logfile);
        $this->AtsJobModel = new Ats_Job();
        $this->AtsTypeModel = new Ats_Type();
        $this->AtsFeedModel = new Ats_Feed();
        $this->AtsFeedTypeModel = new Ats_Feed_Type();
        $this->AtsFeedListingsModel = new Ats_Feed_Listing();

        // load allowable ATS types
        $this->_atsTypes = $this->AtsTypeModel->getAll();

        // load allowable ATS feed types
        $this->_atsFeedTypes = $this->AtsFeedTypeModel->getAll();
    }

    /**
     * Runs the Taleo ATS.
     *
     * @access  public
     * @return  void
     */
    public function taleoAction()
    {
        // get the start time
        $startTime = time();

        // load the Taleo scraper
        $Taleo = new Skookum_Ats_Taleo($this->_qp);

        // retrieve all Taleo job lists for scraping
        $feedUrls = $this->AtsFeedModel->getAllByAtsType($this->_atsTypes['Taleo']['id']);
        foreach ($feedUrls as $feed) {

            $this->_log('Parsing feed: ' . $feed['name']);

            // get the feed type
            $feedType = $this->_atsFeedTypes[ $feed['feed_type_id'] ]['short_name'];

            // scrape the feed
            $jobDetailUrls = $Taleo->scrapeJobListings($feed, $feedType);
            if (!empty($jobDetailUrls)) {

                // store the listings
                $this->AtsFeedListingsModel->updateAll($feed['id'], $jobDetailUrls);

                // scrape the details
                foreach ($jobDetailUrls as $jobUrl) {

                    try {

                        // scrape details
                        $jobDetails = $Taleo->scrapeJobDetails($jobUrl, $feed['url']);
                        if (!empty($jobDetails)) {

                            // set the appropriate subdomain
                            $jobDetails['subdomain'] = $feed['subdomain'];

                            // ensure we have a posted date
                            if (empty($jobDetails['date_posted'])) {
                                $jobDetails['date_posted'] = time();
                            }

                            // add or update a search entry
                            $id = $this->AtsJobModel->exists($feed['id'], $jobDetails['job_id']);
                            if ($id !== FALSE) {
                                $this->_log('Existing job found. Updating database and search index.');

                                // update the uob
                                $this->AtsJobModel->update($feed['id'], $jobDetails, $feed['user_id']);

                                // update search index
                                $jobDetails['id'] = $id;
                                $this->Search->updateJob($jobDetails);
                            } else {
                                $this->_log('New job found. Adding to database and search index.');

                                // create the job
                                $jobDetails['id'] = $this->AtsJobModel->update($feed['id'], $jobDetails, $feed['user_id']);

                                // add to search index
                                $this->Search->indexJob($jobDetails);
                            }

                            // update the last run time for the listing
                            $this->AtsFeedListingsModel->updateLastRanTimestamp($jobUrl);

                            $this->_log('Added job: ' . $jobUrl);
                        } else {
                            // increment the failed count, potentially deleting the job
                            $deleted_job_id = $this->AtsJobModel->incrementFailedCount($jobUrl);
                            if ($deleted_job_id !== FALSE) {
                                // if deleting, remove from search index
                                $this->Search->deleteJob($deleted_job_id);
                            }
                        }

                    } catch (Exception $e) {
                        error_log($e->getMessage());
                    }

                }

            }

            // update the last ran timestamp
            $this->AtsFeedModel->updateLastRanTimestamp($feed['id']);

            // check for jobs that didn't get updated and increment failed count
            $this->AtsJobModel->updateFailedCount($feed['id'], $startTime);

            // check for jobs to close out
            $this->_log('Finding jobs to close that have failed_attempts >= 2.');
            $this->AtsJobModel->closeJobs($feed['id']);

            // get jobs to close out
            $jobs = $this->AtsJobModel->getClosedJobs($feed['id']);
            if (!empty($jobs)) {
                foreach ($jobs as $job) {
                    // delete job from search index
                    $this->Search->deleteJob($job['id']);
                }
            }

            // check for jobs to set the deleted flag
            $this->AtsJobModel->deleteClosedJobs($feed['id']);

        }

        // optimize the search index
        $this->Search->optimizeIndex();

        error_log('Completed Taleo job update.');
    }

    /**
     * Runs the ICIMS ATS.
     *
     * @access  public
     * @return  void
     */
    public function icimsAction()
    {
        // get the start time
        $startTime = time();

        // load the ICIMS scraper
        $Icims = new Skookum_Ats_Icims($this->_qp);

        // retrieve all ICIMS job lists for scraping
        $feedUrls = $this->AtsFeedModel->getAllByAtsType($this->_atsTypes['ICIMS']['id']);
        foreach ($feedUrls as $feed) {

            error_log('Parsing feed: ' . $feed['name']);

            // get the feed type
            $feedType = $this->_atsFeedTypes[ $feed['feed_type_id'] ]['short_name'];

            // scrape the feed
            $jobDetailUrls = $Icims->scrapeJobListings($feed, $feedType);
            if (!empty($jobDetailUrls)) {

                // store the listings
                $this->AtsFeedListingsModel->updateAll($feed['id'], $jobDetailUrls);

                // scrape the details
                foreach ($jobDetailUrls as $jobUrl) {

                    try {

                        // scrape details
                        $jobDetails = $Icims->scrapeJobDetails($jobUrl, $feed['url']);
                        if (!empty($jobDetails)) {

                            // set the appropriate subdomain
                            $jobDetails['subdomain'] = $feed['subdomain'];

                            // ensure we have a posted date
                            if (empty($jobDetails['date_posted'])) {
                                $jobDetails['date_posted'] = time();
                            }

                            // add or update a search entry
                            $id = $this->AtsJobModel->exists($feed['id'], $jobDetails['job_id']);
                            if ($id !== FALSE) {

                                // update the job in the db
                                $this->AtsJobModel->update($feed['id'], $jobDetails, $feed['user_id']);

                                // update search index of the job
                                $jobDetails['id'] = $id;

                                $this->Search->updateJob($jobDetails);

                            } else {
                                // create the job
                                $jobDetails['id'] = $this->AtsJobModel->update($feed['id'], $jobDetails, $feed['user_id']);

                                // add to search index
                                $this->Search->indexJob($jobDetails);
                            }

                            // update the last run time for the listing
                            $this->AtsFeedListingsModel->updateLastRanTimestamp($jobUrl);

                            error_log('Added job: ' . $jobUrl);
                        } else {

                            // increment the failed count, potentially deleting the job
                            $deleted_job_id = $this->AtsJobModel->incrementFailedCount($jobUrl);
                            if ($deleted_job_id !== FALSE) {
                                // if deleting, remove from search index
                                $this->Search->deleteJob($deleted_job_id);
                            }

                        }

                    } catch (Exception $e) {
                        error_log($e->getMessage());
                    }

                }

            }

            // update the last ran timestamp
            $this->AtsFeedModel->updateLastRanTimestamp($feed['id']);

            // check for jobs that didn't get updated and increment failed count
            $this->AtsJobModel->updateFailedCount($feed['id'], $startTime);

            // check for jobs to close out
            $this->_log('Finding jobs to close that have failed_attempts >= 2.');
            $this->AtsJobModel->closeJobs($feed['id']);

            // get jobs to close out
            $jobs = $this->AtsJobModel->getClosedJobs($feed['id']);
            if (!empty($jobs)) {
                foreach ($jobs as $job) {
                    // delete job from search index
                    $this->Search->deleteJob($job['id']);
                }
            }

            // check for jobs to set the deleted flag
            $this->AtsJobModel->deleteClosedJobs($feed['id']);

        }

        error_log('Completed ICIMS job update.');
    }

    /**
     * Runs the DataFrenzy
     *
     * @access  public
     * @return  void
     */
    public function datafrenzyAction()
    {
        // get the start time
        $startTime = time();

        // load the DataFrenzy scraper
        $Datafrenzy = new Skookum_Ats_Datafrenzy($this->_qp);

        // retrieve all DataFrenzy job lists for scraping
        $feedUrls = $this->AtsFeedModel->getAllByAtsType($this->_atsTypes['DataFrenzy']['id']);
        foreach ($feedUrls as $feed) {

            error_log('Parsing feed: ' . $feed['name']);

            // get the feed type
            //$feedType = $this->_atsFeedTypes[ $feed['feed_type_id'] ]['short_name'];

            // scrape the feed
            $jobDetails = $Datafrenzy->scrapeJobListings($feed, 'xml');
            if (!empty($jobDetails)) {

                error_log('Total jobs for import: ' . count($jobDetails));

                // store the listings
                //$this->AtsFeedListingsModel->updateAll($feed['id'], $jobDetailUrls);
                // scrape the details
                foreach ($jobDetails as $jobDetail) {

                    // skip empty jobs
                    if (!empty($jobDetail)) {

                        try {

                            // set the appropriate subdomain
                            $jobDetail['subdomain'] = $feed['subdomain'];

                            // ensure we have a posted date
                            if (empty($jobDetail['date_posted'])) {
                                $jobDetail['date_posted'] = time();
                            }

                            // add or update a search entry
                            $id = $this->AtsJobModel->exists($feed['id'], $jobDetail['job_id']);
                            if ($id !== FALSE) {

                                error_log('Job already exists in database, updating.');

                                // update the job in the db
                                $this->AtsJobModel->update($feed['id'], $jobDetail, $feed['user_id']);

                                // update search index of the job
                                $jobDetail['id'] = $id;

                                // update the job in search
                                $this->Search->updateJob($jobDetail);

                            } else {

                                // create the job
                                $jobDetail['id'] = $this->AtsJobModel->update($feed['id'], $jobDetail, $feed['user_id']);

                                // add to search index
                                $this->Search->indexJob($jobDetail);
                            }

                        } catch (Exception $e) {
                            error_log($e->getMessage());
                        }

                    } else {
                        error_log("ERROR RETRIEVING JOB DETAILS.");
                    }

                }

            }

            // update the last ran timestamp
            $this->AtsFeedModel->updateLastRanTimestamp($feed['id']);

            // check for jobs that didn't get updated and increment failed count
            $this->AtsJobModel->updateFailedCount($feed['id'], $startTime);

            // check for jobs to close out
            $this->_log('Finding jobs to close that have failed_attempts >= 2.');
            $this->AtsJobModel->closeJobs($feed['id']);

            // get jobs to close out
            $jobs = $this->AtsJobModel->getClosedJobs($feed['id']);
            if (!empty($jobs)) {
                foreach ($jobs as $job) {
                    // delete job from search index
                    $this->Search->deleteJob($job['id']);
                }
            }

            // check for jobs to set the deleted flag
            $this->AtsJobModel->deleteClosedJobs($feed['id']);

        }

        // commit the search index
        $this->Search->commitIndex();

        // finish up
        error_log('Completed DataFrenzy job update.');
    }

    /**
     * Runs the HRSmart scraping
     *
     * @access  public
     * @return  void
     */
    public function hrsmartAction()
    {
        // get the start time
        $startTime = time();

        // load the HRSmart scraper
        $hrsmart = new Skookum_Ats_Hrsmart($this->_qp);

        // retrieve all DataFrenzy job lists for scraping
        $feedUrls = $this->AtsFeedModel->getAllByAtsType($this->_atsTypes['HRSmart']['id']);
        foreach ($feedUrls as $feed) {

            error_log('Parsing feed: ' . $feed['name']);

            // get the feed type
            $feedType = $this->_atsFeedTypes[ $feed['feed_type_id'] ]['short_name'];

            // scrape the feed
            $jobDetailUrls = $hrsmart->scrapeJobListings($feed, 'html');
            $jobDetailExtras = $hrsmart->getExtra();
            if (!empty($jobDetailUrls)) {

                // store the listings
                $this->AtsFeedListingsModel->updateAll($feed['id'], $jobDetailUrls);

                // scrape the details
                foreach ($jobDetailUrls as $i => $jobUrl) {
                    try {

                        // scrape details
                        $jobDetails = $hrsmart->scrapeJobDetails($jobUrl, $feed['url']);
                        $jobDetails = array_merge($jobDetailExtras[$i], $jobDetails);
                        if (!empty($jobDetails)) {

                            // set the appropriate subdomain
                            $jobDetails['subdomain'] = $feed['subdomain'];

                            // ensure we have a posted date
                            if (empty($jobDetails['date_posted'])) {
                                $jobDetails['date_posted'] = time();
                            }

                            // add or update a search entry
                            $id = $this->AtsJobModel->exists($feed['id'], $jobDetails['job_id']);
                            if ($id !== FALSE) {

                                // update the job in the db
                                $this->AtsJobModel->update($feed['id'], $jobDetails, $feed['user_id']);

                                // update search index of the job
                                $jobDetails['id'] = $id;

                                $this->Search->updateJob($jobDetails);

                            } else {
                                // create the job
                                $jobDetails['id'] = $this->AtsJobModel->update($feed['id'], $jobDetails, $feed['user_id']);
                                // add to search index
                                $this->Search->indexJob($jobDetails);
                            }

                            // update the last run time for the listing
                            $this->AtsFeedListingsModel->updateLastRanTimestamp($jobUrl);

                        } else {

                            // increment the failed count, potentially deleting the job
                            $deleted_job_id = $this->AtsJobModel->incrementFailedCount($jobUrl);
                            if ($deleted_job_id !== FALSE) {
                                // if deleting, remove from search index
                                $this->Search->deleteJob($deleted_job_id);
                            }

                        }

                    } catch (Exception $e) {
                        error_log($e->getMessage());
                    }

                }


            }

            // update the last ran timestamp
            $this->AtsFeedModel->updateLastRanTimestamp($feed['id']);

            // check for jobs that didn't get updated and increment failed count
            $this->AtsJobModel->updateFailedCount($feed['id'], $startTime);

            // check for jobs to close out
            $this->_log('Finding jobs to close that have failed_attempts >= 2.');
            $this->AtsJobModel->closeJobs($feed['id']);

            // get jobs to close out
            $jobs = $this->AtsJobModel->getClosedJobs($feed['id']);
            if (!empty($jobs)) {
                foreach ($jobs as $job) {
                    // delete job from search index
                    $this->Search->deleteJob($job['id']);
                }
            }

            // check for jobs to set the deleted flag
            $this->AtsJobModel->deleteClosedJobs($feed['id']);

        }

        error_log('Completed HRSmart job update.');
    }

    /**
     * Pull data from a JobTarget XML feed.
     *
     * @access  public
     * @return  void
     */
    public function jobtargetAction()
    {
        // the start time
        $startTime = time();

        // send initialization email
        $this->_sendmail(
            '[Parallon Jobs] - JobTarget Cronjob Started',
            'The cronjob has started pulling reporting data at ' . date('m-d-Y H:i:s', $startTime) . ' EST.' .
            '<br /><br />* Output is being logged to: ' . $this->_logfile
        );

        // load the JobTarget scraper
        $JobTarget = new Skookum_Ats_Jobtarget($this->_qp);

        // retrieve all DataFrenzy job lists for scraping
        $feedUrls = $this->AtsFeedModel->getAllByAtsType($this->_atsTypes['JobTarget']['id']);
        foreach ($feedUrls as $feed) {

            $this->_log('Parsing feed: ' . $feed['name']);

            // scrape the feed
            $jobDetails = $JobTarget->scrapeJobListings($feed, 'xml');
            if (!empty($jobDetails)) {

                $totalCount = count($jobDetails);
                $this->_log('Total jobs for import: ' . $totalCount);

                // keep track of parsed count
                $count = 1;

                // scrape the details
                foreach ($jobDetails as $jobDetail) {

                    $this->_log('Parsing job ' . $count++ . ' of ' . $totalCount);

                    // skip empty jobs
                    if (!empty($jobDetail)) {

                        try {

                            // set the appropriate subdomain
                            $jobDetail['subdomain'] = $feed['subdomain'];

                            // ensure we have a posted date
                            if (empty($jobDetail['date_posted'])) {
                                $jobDetail['date_posted'] = date('Y-m-d H:i:s');
                            }

                            // add or update a search entry
                            $id = $this->AtsJobModel->exists($feed['id'], $jobDetail['job_id']);
                            if ($id !== FALSE) {

                                $this->_log('Job already exists in database, updating db and search index.');

                                // update the job in the db
                                $this->AtsJobModel->update($feed['id'], $jobDetail, $feed['user_id']);

                                // update search index of the job
                                $jobDetail['id'] = $id;

                                // update the job in search
                                $this->Search->updateJob($jobDetail);

                            } else {
                                $this->_log('New job found. Adding to db and search index.');

                                // create the job
                                $jobDetail['id'] = $this->AtsJobModel->update($feed['id'], $jobDetail, $feed['user_id']);

                                // add to search index
                                $this->Search->indexJob($jobDetail);
                            }

                        } catch (Exception $e) {
                            $this->_log('[EXCEPTION] ' . $e->getMessage());
                        }

                    } else {
                        $this->_log("[ERROR] ERROR RETRIEVING JOB DETAILS.");
                    }

                }

            }

            try {

                // optimize index before handling deletes
                $this->_log('Optimizing the search index before handling deletions.');
                $this->Search->optimizeIndex();

                // update the last ran timestamp
                $this->_log('Updating the last ran timestamp of the feed.');
                $this->AtsFeedModel->updateLastRanTimestamp($feed['id']);

                // check for jobs that didn't get updated and increment failed count
                $this->_log('Updating the failed count of jobs that didnt run.');
                $this->AtsJobModel->updateFailedCount($feed['id'], $startTime);

                // check for jobs to close out
                $this->_log('Finding jobs to close that have failed_attempts >= 2.');
                $this->AtsJobModel->closeJobs($feed['id']);

                // get jobs to close out
                $jobs = $this->AtsJobModel->getClosedJobs($feed['id']);
                if (!empty($jobs)) {
                    $this->_log('Handling search index deletion of ' . count($jobs) . ' closed jobs.');
                    foreach ($jobs as $job) {
                        // delete job from search index
                        $this->_log('Attempting to delete job #' . $job['id'] . ' from the search index.');
                        if ($this->Search->deleteJob($job['id'])) {
                            $this->_log('Job deleted.');
                        } else {
                            $this->_log('Error deleting job.');
                        }
                    }
                }

                // check for jobs to set the deleted flag
                $this->_log('Setting deletion flag on any jobs that have been closed for > 30 days.');
                $this->AtsJobModel->deleteClosedJobs($feed['id']);

            } catch (Exception $e) {
                $this->_log('[EXCEPTION] ' . $e->getMessage());
            }

        }

        // commit and optimize the search index
        $this->_log('Optimizing the search index.');
        $this->Search->optimizeIndex();

        // finish up
        $this->_log('Completed JobTarget job update.');
    }


    public function silkroadAction()
    {
        // load the JobTarget scraper
        $Silkroad = new Skookum_Ats_Silkroad($this->_qp);

        // retrieve all DataFrenzy job lists for scraping
        $feedUrls = $this->AtsFeedModel->getAllByAtsType($this->_atsTypes['Silkroad']['id']);
        foreach ($feedUrls as $feed) {

            error_log('Parsing feed: ' . $feed['name']);
            // scrape the feed
            $jobDetails = $Silkroad->scrapeJobListings($feed, 'xml');
            if (!empty($jobDetails)) {

                error_log('Total jobs for import: ' . count($jobDetails));

                // store the listings
                //$this->AtsFeedListingsModel->updateAll($feed['id'], $jobDetailUrls);
                // scrape the details
                foreach ($jobDetails as $jobDetail) {

                    // skip empty jobs
                    if (!empty($jobDetail)) {

                        try {

                            // set the appropriate subdomain
                            $jobDetail['subdomain'] = $feed['subdomain'];

                            // ensure we have a posted date
                            if (empty($jobDetail['date_posted'])) {
                                $jobDetail['date_posted'] = time();
                            }

                            // add or update a search entry
                            $id = $this->AtsJobModel->exists($feed['id'], $jobDetail['job_id']);
                            if ($id !== FALSE) {

                                error_log('Job already exists in database, updating.');

                                // update the job in the db
                                $this->AtsJobModel->update($feed['id'], $jobDetail, $feed['user_id']);

                                // update search index of the job
                                $jobDetail['id'] = $id;

                                // update the job in search
                                $this->Search->updateJob($jobDetail);

                            } else {

                                // create the job
                                $jobDetail['id'] = $this->AtsJobModel->update($feed['id'], $jobDetail, $feed['user_id']);

                                // add to search index
                                $this->Search->indexJob($jobDetail);
                            }

                        } catch (Exception $e) {
                            error_log($e->getMessage());
                        }

                    } else {
                        error_log("ERROR RETRIEVING JOB DETAILS.");
                    }

                }

            }

            // update the last ran timestamp
            $this->AtsFeedModel->updateLastRanTimestamp($feed['id']);
        }

        // commit the search index
        $this->Search->commitIndex();

        // finish up
        error_log('Completed Silkroad job update.');
    }

    /**
     * Update sitemaps for all clients.
     *
     * @access  public
     * @return  void
     */
    public function sitemapAction()
    {
        // load the users model
        $Users = new Users();

        // grab the site domain
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/global.ini', APPLICATION_ENV);
        $config = $config->toArray();

        // grab all existing clients and their subdomains for crawling
        $clients = $Users->getAllClientsForCrawl();
        if (!empty($clients)) {
            foreach ($clients as $client) {
                // load up a new instance of sitemap
                $sitemap = new Skookum_Caterpillar_Sitemap('http://' . $client['subdomain'] . '.' . $config['domain'], $client['id']);

                // handle scenario of deleted clients
                if ($client['deleted'] == 1) {
                    $sitemap->delete();
                } else {
                    error_log('Begin crawling sitemap');
                    $sitemap->crawl();
                }
            }
        }
    }

    /**
     * Delete old log files.
     *
     * @access  public
     * @return  void
     */
    public function cleanupAction()
    {
        $dir = APPLICATION_PATH . '/data/logs/';
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while ($file = readdir($dh)) {
                    $filepath = $dir . $file;
                    if(!is_dir($filepath) && $file != '.' && $file != '..') {
                        if (filemtime($filepath) < strtotime('-10 days')) {
                            error_log('Deleting old file: ' . $file);
                            @unlink($filepath);
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieve interesting statistics on the search index.
     *
     * @access  public
     * @return  void
     */
    public function statsAction()
    {
        $this->Search->getAllJobCount();
    }

    /**
     * Regenerate sitemaps from existing crawler data.
     *
     * @access  public
     * @return  void
     */
    public function regeneratesitemapAction()
    {
        // load the users model
        $Users = new Users();

        // grab the site domain
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/global.ini', APPLICATION_ENV);
        $config = $config->toArray();

        // grab all existing clients and their subdomains for crawling
        $clients = $Users->getAllClientsForCrawl();
        if (!empty($clients)) {
            foreach ($clients as $client) {
                // load up a new instance of sitemap
                $sitemap = new Skookum_Caterpillar_Sitemap('http://' . $client['subdomain'] . '.' . $config['domain'], $client['id']);

                // handle scenario of deleted clients
                if ($client['deleted'] == 1) {
                    $sitemap->delete();
                } else {
                    $sitemap->build();
                }
            }
        }
    }

    /**
     * Run this to regenerate the search index when a substantial change
     * is made or a new field is added.
     *
     * @access  public
     */
    public function regeneratesearchindexAction()
    {
        try {
            error_log("Retrieving all jobs...");
            $jobs = $this->AtsJobModel->getAll();
            error_log("Regenerating search index...");
            $this->Search->regenerateIndex($jobs);
            error_log('Mission complete');
        } catch (Exception $e) {
            var_dump($e);
        }
        die;
    }

    /**
     * Default mailer.
     *
     * @access  public
     * @param   string  $subject
     * @param   string  $message
     * @return  void
     */
    protected function _sendmail($subject, $message)
    {
        try {

            $mail = new Zend_Mail();
            $mail->setSubject($subject)
                 ->setFrom('do-not-reply@parallonjobs.com')
                 ->addTo('corey@skookum.com')
                 ->setBodyHTML($message)
                 ->send();

        } catch (Exception $e) {
            $this->_log($e->getMessage());
        }
    }

    /**
     * Useful logging function for imports so you can tail something as it runs.
     *
     * @access  private
     * @param   mixed       $data
     */
    private function _log($data)
    {
        file_put_contents($this->_logfile, print_r($data, true) . PHP_EOL, FILE_APPEND);
        error_log(print_r($data, true));
    }
}
