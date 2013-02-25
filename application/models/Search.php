<?php
require_once 'Zend/Feed.php';
require_once 'Zend/Search/Lucene.php';

/**
 * Custom Lucene searching. Useful documentation found at:
 * http://framework.zend.com/manual/en/zend.search.lucene.query-language.html
 */
class Search extends Skookum_Model
{

    protected $index;

    /**
     * Path to a log file.
     */
    protected $_logfile;

    /**
     * Ensure indexes get created with restrictive permissions.
     *
     * @access  public
     */
    public function __construct($logfile = NULL)
    {
        parent::__construct();

        // handles logging
        if (!empty($logfile)) {
            $this->_logfile = $logfile;
        } else {
            $this->_logfile = APPLICATION_PATH . '/data/logs/' . date('Y-m-d-His') . '.log';
        }

        Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0777);
        Zend_Search_Lucene::setTermsPerQueryLimit(128);

        // log the path for help
        $this->_log(APPLICATION_PATH . '/data/search/jobs');
    }

    /**
     * Search for particular job matches.
     *
     * @access  public
     * @param   string  $subdomain
     * @param   string  $title
     * @param   string  $location
     * @param   string  $category
     * @param   mixed   $limit
     * @return  mixed
     */
    public function jobs($subdomain, $title = '', $location = '', $category = '', $limit = NULL)
    {
        // determine if setting a result limit
        if (!is_null($limit)) {
            Zend_Search_Lucene::setResultSetLimit($limit);
        }

        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

        // build the query string
        $queryStr = array();

        //  restrict searches by the subdomain
        $queryStr[] = '+subdomain:(' . $subdomain . ')';

        // check for a title
        if (!empty($title)) {
            // handle spaces as phrases
            if (strpos(trim($title), ' ') !== FALSE) {
                $queryStr[] = '+name:("' . $this->sanitize($title) . '"~10)';
            } else {
                $queryStr[] = '+name:(' . $this->sanitize($title) . ')';
            }
        }

        // check for a location
        if (!empty($location)) {
            // handle spaces as phrases
            if (strpos(trim($location), ' ') !== FALSE) {
                $queryStr[] = '+location:("' . Clean::uristub($location) . '"~3)';
            } else {
                $queryStr[] = '+location:(' . Clean::uristub($location) . ')';
            }
        }

        // check for a category
        if (!empty($category)) {
            // handle spaces as phrases
            if (strpos(trim($category), ' ') !== FALSE) {
                $queryStr[] = '+category:("' . Clean::uristub($category) . '"~4)';
            } else {
                $queryStr[] = '+category:(' . Clean::uristub($category) . ')';
            }
        }
        
        // merge the query terms
        $queryStr = implode(' ', $queryStr);
       
        // try out sorting
        if (empty($title)) {
            // perform the query
            return $this->index->find(
                $queryStr,
                'date_posted', SORT_REGULAR, SORT_DESC
            );
        } else {
            // perform the query
            return $this->index->find(
                $queryStr,
                'score', SORT_NUMERIC, SORT_DESC,
                'date_posted', SORT_NUMERIC, SORT_DESC
            );
        }
        
    }

    /**
     * Search for particular category matches.
     *
     * @access  public
     * @param   string  $category
     * @param   string  $subdomain
     * @return  mixed
     */
    public function category($category, $subdomain)
    {
        // if multiple words, try flipping them
        $words = explode(' ', $category);
        if (count($words) > 1) {
            // remove bogus words
            foreach ($words as $k => $w) {
                // if less than 3 chars, remove
                if (strlen($w) < 3) {
                    unset($words[$k]);
                }
            }
        }

        // if we still have multiple words
        if (count($words) > 1) {
            $sql = array();
            foreach ($words as $w) {
                $sql[] = sprintf('(SELECT category AS label, category AS value
                                FROM ats_jobs
                                WHERE category LIKE %s
                                ORDER BY category ASC)',
                                $this->_db->quote('%' . $w . '%'));
            }

            $sql = 'SELECT DISTINCT category AS label, category AS value
                    FROM ' . implode(' UNION ', $sql) . '
                    ORDER BY category ASC';

        } else {
            $sql = sprintf('SELECT DISTINCT category AS label, category AS value
                            FROM ats_jobs
                            WHERE category LIKE %s
                            ORDER BY category ASC',
                            $this->_db->quote('%' . $words[0] . '%'));
        }

        return $this->_db->query($sql)->fetchAll();
    }

    /**
     * Search for particular location matches.
     *
     * @access  public
     * @param   string  $location
     * @param   string  $subdomain
     * @return  mixed
     */
    public function location($location, $subdomain)
    {
        // if multiple words, try flipping them
        $words = explode(' ', $location);
        if (count($words) > 1) {
            // remove bogus words
            foreach ($words as $k => $w) {
                // if less than 3 chars, remove
                if (strlen($w) < 3) {
                    unset($words[$k]);
                }
            }
        }

        // if we still have multiple words
        if (count($words) > 1) {
            $sql = array();
            foreach ($words as $w) {
                $sql[] = sprintf('(SELECT location AS label, location AS value
                                FROM ats_jobs
                                WHERE location = %s
                                ORDER BY location ASC)',
                                $this->_db->quote('%' . $w . '%'));
            }

            $sql = 'SELECT DISTINCT location AS label, location AS value
                    FROM ' . implode(' UNION ', $sql) . '
                    ORDER BY location ASC';

        } else {
            $sql = sprintf('SELECT DISTINCT location AS label, location AS value
                            FROM ats_jobs
                            WHERE location LIKE %s
                            ORDER BY location ASC',
                            $this->_db->quote('%' . $words[0] . '%'));
        }

        return $this->_db->query($sql)->fetchAll();
    }

    /**
     * Index a particular job.
     *
     * @access  public
     * @param   array   $job
     * @param   mixed   $uristub
     */
    public function indexJob($job, $uristub = NULL)
    {
        // split categories into words
        $category = !empty($job['category']) ? Clean::uristub($job['category']) : NULL;

        // split locations into words
        $location = !empty($job['location']) ? Clean::uristub($job['location']) : NULL;

        // fix the city
        $city = !empty($job['city']) ? Clean::uristub($job['city']) : NULL;

        // fix the state
        $state = !empty($job['state']) ? Clean::uristub($job['state']) : NULL;

        // fix the schedule
        $schedule = !empty($job['schedule']) ? $this->sanitize($job['schedule']) : '';

        // generate the unique uristub
        if (is_null($uristub)) {
            $str = $job['name'] . (isset($job['job_id']) ? '-' . $job['job_id'] : '');
            $uristub = $this->_simpleUristub($str, 155);
        }

        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

        // handle date posted
        $date_posted = !empty($job['date_posted']) ? date('U', strtotime($job['date_posted'])) : NULL;

        // create the document
        $doc = new Zend_Search_Lucene_Document();
        $doc->addField(Zend_Search_Lucene_Field::Keyword('id', (int) $job['id']));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('job_id', $this->sanitize($job['job_id'])));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('subdomain', $job['subdomain']));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('city', $city));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('state', $state));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('location', $location));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('category', $category));
        $doc->addField(Zend_Search_Lucene_Field::Text('name', $this->sanitize($job['name'])));
        $doc->addField(Zend_Search_Lucene_Field::Keyword('schedule', $schedule));
        $doc->addField(Zend_Search_Lucene_Field::UnIndexed('uristub', $uristub));
        $doc->addField(Zend_Search_Lucene_Field::UnIndexed('date_posted', $date_posted));

        // add the document to the index
        $this->index->addDocument($doc);

        // commit
        $this->index->commit();
    }

    /**
     * Updates a particular job document in the index. Since updates aren't
     * handled, we must delete and re-add.
     *
     * @access  public
     * @param   array   $job
     * @return  mixed
     */
    public function updateJob($job)
    {
        try {
            // either open or create the index
            $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

            $this->_log('Attempting to find job by id: #' . $job['id']);

            // find the job by id
            $documents = $this->index->termDocs(new Zend_Search_Lucene_Index_Term($job['id'], 'id'));
            if (!empty($documents)) {
                $this->_log('Matching job id found in search index: #' . $job['id']);
                foreach ($documents as $docId) {
                    $this->_log('deleting and creating job id #' . $job['id']);
                    // attempt job deletion
                    $this->index->delete($docId);
                    // commit deletion change
                    $this->index->commit();
                    // now handle re-adding the job
                    $this->indexJob($job);
                }
            } else {
                // nothing exists, try to index
                $this->_log('No matching job id found in search index, creating for job id #' . $job['id']);
                $this->indexJob($job);
            }
        } catch (Exception $e) {
            $this->_log(print_r($e, true));
        }
    }

    /**
     * Remove a job from the search index based on it's job id.
     *
     * @access  public
     * @param   int     $id
     * @return  void
     */
    public function deleteJob($id)
    {
        try {
            // either open or create the index
            $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

            // find the matching document
            $documents = $this->index->termDocs(new Zend_Search_Lucene_Index_Term($id, 'id'));
            if (!empty($documents)) {
                foreach ($documents as $docId) {
                    $this->_log('Deleting the document ' . $docId);
                    // attempt job deletion
                    $this->index->delete($docId);
                    // commit deletion changes
                    $this->index->commit();
                }
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->_log(print_r($e, true));
        }

        return false;
    }

    /**
     * Retrieve all jobs by subdomain.
     *
     * @access  public
     * @param   string  $subdomain
     */
    public function getAllJobsBySubdomain($subdomain)
    {
        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

        //  restrict searches by the subdomain
        $queryStr = '+subdomain:(' . $subdomain . ')';

        // perform the query
        return $this->index->find($queryStr);
    }

    /**
     * Retrieve all jobs from the search index.
     *
     * @access  public
     * @return  mixed
     */
    public function getAllJobs($subdomain, $deleted = false)
    {
        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

        $document = array();

        // number of document in the index
        $numDocs = $this->index->count();
        for ($id = 0; $id < $numDocs; $id++) {
            if ($deleted == false) {
                if (!$this->index->isDeleted($id)) {
                    //$this->index->delete($id);
                    $document[$id] = $this->index->getDocument($id);
                }
            } else {
                $document[$id] = $this->index->getDocument($id);
            }
        }

        return $document;
    }

    /**
     * Retrieve pertinent job stats on the search index.
     *
     * @access  public
     * @return  void
     */
    public function getAllJobCount()
    {
        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

        // number of document in the index
        $numDocs = $this->index->count();
        $this->_log('There are ' . $numDocs . ' total documents in the search index.');

        $count = 0;
        $deletedCount = 0;

        for ($id = 0; $id < $numDocs; $id++) {
            if (!$this->index->isDeleted($id)) {
               $count++;
            } else {
                $deletedCount++;
            }
        }

        $this->_log('There are ' . $count . ' active documents in the search index.');
        $this->_log('There are ' . $deletedCount . ' deleted documents in the search index.');
    }

    /**
     * Delete all existing jobs in the search index.
     *
     * @access  public
     * @return  void
     */
    public function deleteAllJobs()
    {
        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');

        // number of document in the index
        $numDocs = $this->index->count();
        for ($id = 0; $id < $numDocs; $id++) {
            if (!$this->index->isDeleted($id)) {
                $this->index->delete($id);
            }
        }

        $this->index->commit();
        $this->index->optimize();
    }

    /**
     * Regenerate the search index by retrieving all jobs and then regenerating
     * them.
     *
     * @access  public
     * @param   array   $jobs
     * @return  void
     */
    public function regenerateIndex($jobs)
    {
        // delete pre-existing documents
        $this->deleteAllJobs();

        // iterate over each result, creating the job
        foreach ($jobs as $job) {
            $this->indexJob($job, $job['uristub']);
        }

        $this->index->commit();
        $this->index->optimize();

        return true;
    }

    /**
     * Either open or create an index.
     *
     * @access  public
     * @param   string  $path
     * @return  Zend_Search_Lucene
     */
    public function openIndex($path)
    {
        try {

            // check for non empty index
            if (!empty($this->index)) {
                $dir = $this->index->getDirectory();
                if (!empty($dir)) {
                    return $this->index;
                }
            }

            return new Zend_Search_Lucene($path, false);

        } catch (Exception $e) {
            $this->_log($e->getMessage());
        }

        $this->_log('Creating search index.');
        return new Zend_Search_Lucene($path, true);
    }

    /**
     * Optimize the search index. Should only be ran after the cron finishes.
     *
     * @access  public
     * @return  void
     */
    public function optimizeIndex()
    {
        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');
        $this->index->optimize();
    }

    /**
     * Commit the search index changes. Should only be ran after the cron finishes.
     *
     * @access  public
     * @return  void
     */
    public function commitIndex()
    {
        // either open or create the index
        $this->index = $this->openIndex(APPLICATION_PATH . '/data/search/jobs');
        $this->index->commit();
        $this->_log($this->index->count() . ' documents indexed.');
    }

    /**
     * Sanitize search data.
     *
     * @access  public
     * @param   mixed   $str
     * @return  string
     */
    public function sanitize($str)
    {
        $str = strip_tags(trim($str));
        $str = preg_replace('/[^a-zA-Z0-9\(\)\$\.]+/', ' ', $str);
        $str = preg_replace('/\s{3,}/', ' - ', $str);
        return preg_replace('/\s+/', ' ', $str);
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

