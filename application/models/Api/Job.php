<?php
class Api_Job extends Skookum_Api_Server_Model_Api
{

    /**
     * Retrieves the total number of possible results given the criteria.
     *
     * @access  public
     * @param   int     $user_id
     * @return  int
     */
    public function getPaginationTotalByUserId($user_id)
    {
        $sql = sprintf('SELECT COUNT(1) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY created_by',
                        $user_id);

        $result = $this->_db->query($sql)->fetch(0);
        return (!empty($result['count'])) ? (int) $result['count'] : 0;
    }

    /**
     * Retrieves all jobs ordered for general use.
     *
     * @access  public
     * @param   int     $user_id
     * @param   int     $page
     * @param   int     $perPage
     * @param   string  $orderBy
     * @param   string  $sortOrder
     * @return  array
     */
    public function getAllByUserId(
        $user_id,
        $page = 0,
        $perPage = 25,
        $orderBy = 'title',
        $sortOrder = 'ASC'
    )
    {
        // tiny fix for an odd mapping
        if ($orderBy == 'title') {
            $orderBy = 'name';
        } else if ($orderBy == 'last_modified') {
            $orderBy = 'modified_ts';
        }


        $offset = $this->getPaginationOffset($page, $perPage);
        $sql = sprintf('SELECT id, job_id, uristub, company, name, location, city,
                        state, address, zipcode, category, department, schedule,
                        shift, description, qualifications, num_openings, years_exp,
                        job_url, apply_url, outbound_link_url, apply_phone,
                        date_posted, created_ts, modified_ts,
                        editable, modal_style, hide_apply
                        FROM ats_jobs
                        WHERE created_by = %d
                        AND closed = 0
                        AND deleted = 0
                        ORDER BY `%s` %s
                        LIMIT %d,%d',
                        $user_id,
                        $orderBy,
                        $sortOrder,
                        $offset,
                        $perPage);

        // get result(s)
        $results = $this->_db->query($sql)->fetchAll();
        if (!empty($results)) {
            foreach ($results as $k => $row) {
                $results[$k]['url'] = $this->_domain . 'job/details/' . Clean::uristub($row['location']) . '/' . Clean::uristub($row['category']) . '/' . Clean::uristub($row['uristub']);
            }
        }
        // get number of results
        $numResults = count($results);
        // get total number of results without filters
        $totalResults = $this->getPaginationTotalByUserId($user_id);
        // get total number of pages
        $totalPages = $totalResults > 0 && $totalResults > $perPage ? ceil($totalResults / $perPage) : 0;
        // check if we have another page
        $nextPage = $totalPages > $page ? $page + 1 : null;

        // revert order by
        if ($orderBy == 'name') {
            $orderBy = 'title';
        } else if ($orderBy == 'modified_ts') {
            $orderBy = 'last_modified';
        }

        // handle response pagination details
        return array(
            'results' => $results,
            'url' => $this->_domain . 'jobs/all/',
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'sortOrder' => $sortOrder,
            'nextPage' => $nextPage,
            'numResults' => $numResults,
            'totalResults' => $totalResults,
            'totalPages' => $totalPages
        );
    }

    /**
     * Retrieves all job titles ordered for general use.
     *
     * @access  public
     * @param   int     $user_id
     * @param   int     $page
     * @param   int     $perPage
     * @param   string  $sortOrder
     * @return  array
     */
    public function getTitlesByUserId(
        $user_id,
        $page = 0,
        $perPage = 25,
        $sortOrder = 'ASC'
    )
    {
        // get limited resultset
        $offset = $this->getPaginationOffset($page, $perPage);
        $sql = sprintf('SELECT id, job_id, uristub, name, location, category
                        FROM ats_jobs
                        WHERE created_by = %d
                        AND closed = 0
                        AND deleted = 0
                        ORDER BY `name` %s
                        LIMIT %d,%d',
                        $user_id,
                        $sortOrder,
                        $offset,
                        $perPage);

        // get result(s)
        $results = $this->_db->query($sql)->fetchAll();
        if (!empty($results)) {
            foreach ($results as $k => $row) {
                $results[$k]['url'] = $this->_domain . 'job/details/' . Clean::uristub($row['location']) . '/' . Clean::uristub($row['category']) . '/' . Clean::uristub($row['uristub']);
                unset($results[$k]['category'], $results[$k]['location'], $results[$k]['uristub']);
            }
        }

        // get number of results
        $numResults = count($results);
        // get total number of results without filters
        $totalResults = $this->getPaginationTotalByUserId($user_id);
        // get total number of pages
        $totalPages = $totalResults > 0 && $totalResults > $perPage ? ceil($totalResults / $perPage) : 0;
        // check if we have another page
        $nextPage = $totalPages > $page ? $page + 1 : null;

        // handle response pagination details
        return array(
            'results' => $results,
            'url' => $this->_domain . 'jobs/all/',
            'page' => $page,
            'perPage' => $perPage,
            'sortOrder' => $sortOrder,
            'nextPage' => $nextPage,
            'numResults' => $numResults,
            'totalResults' => $totalResults,
            'totalPages' => $totalPages
        );
    }

    /**
     * Retrieve jobs within a given radius of a zipcode.
     *
     * @access  public
     * @link    http://www.bradino.com/mysql/zipcode-radius-search/
     * @param   int     $user_id
     * @param   string  $zipcode
     * @param   int     $radius
     * @param   int     $page
     * @param   int     $perPage
     * @param   string  $orderBy
     * @param   string  $sortOrder
     * @return  mixed
     */
    public function getJobsWithinRadius(
        $user_id,
        $zipcode,
        $radius = 10,
        $page = 0,
        $perPage = 25,
        $orderBy = 'title',
        $sortOrder = 'ASC'
    )
    {
        // fix the zipcode if necessary
        if (strpos($zipcode, '-') !== FALSE) {
            $zipcode = trim(substr($zipcode, 0, strpos($zipcode, '-')));
        }

        // tiny fix for an odd mapping
        if ($orderBy == 'title') {
            $orderBy = 'name';
        } else if ($orderBy == 'last_modified') {
            $orderBy = 'modified_ts';
        }

        // retrieve the latitude and longitude
        $sql = sprintf('SELECT latitude, longitude FROM zipcodes WHERE postal_code = %d', $zipcode);
        $result = $this->_db->query($sql)->fetch();
        if (empty($result)) {
            return false;
        }

        // get total number of results
        $sql = sprintf('SELECT SUM(`count`) AS `count`
                        FROM
                            (SELECT COUNT(1) AS `count`,
                            (3959 * ACOS(
                                 COS(RADIANS(%1$s))
                                 * COS(RADIANS(latitude))
                                 * COS(RADIANS(longitude) - RADIANS(%2$s))
                                 + SIN(RADIANS(%1$s))
                                 * SIN(RADIANS(latitude))
                            )) AS distance
                            FROM ats_jobs
                            INNER JOIN zipcodes ON (ats_jobs.zipcode = zipcodes.postal_code)
                            WHERE ats_jobs.created_by = %3$d
                            AND ats_jobs.closed = 0
                            AND ats_jobs.deleted = 0
                            GROUP BY zipcodes.postal_code
                            HAVING distance <= %4$d) AS crapper',
                        $this->_db->quote($result['latitude']),
                        $this->_db->quote($result['longitude']),
                        $user_id,
                        $radius);

        $resultCount = $this->_db->query($sql)->fetch();
        if (empty($resultCount) || $resultCount['count'] == 0) {
            // revert order by
            if ($orderBy == 'name') {
                $orderBy = 'title';
            } else if ($orderBy == 'modified_ts') {
                $orderBy = 'last_modified';
            }

            return array(
                'results' => array(),
                'url' => $this->_domain . 'jobs/all/',
                'zipcode' => $zipcode,
                'radius' => $radius,
                'page' => $page,
                'perPage' => $perPage,
                'orderBy' => $orderBy,
                'sortOrder' => $sortOrder,
                'nextPage' => null,
                'numResults' => 0,
                'totalResults' => 0,
                'totalPages' => 0
            );
        }

        $totalResults = (int) $resultCount['count'];

        // calculate the offset
        $offset = $this->getPaginationOffset($page, $perPage);

        // retrieve jobs within a given zipcode
        $sql = sprintf('SELECT zipcodes.postal_code, zipcodes.latitude, zipcodes.longitude,
                        zipcodes.country, zipcodes.timezone, ats_jobs.location, ats_jobs.city, ats_jobs.state,
                        ats_jobs.id, ats_jobs.job_id, ats_jobs.uristub, ats_jobs.company,
                        ats_jobs.name, ats_jobs.location, ats_jobs.address, ats_jobs.category,
                        ats_jobs.department, ats_jobs.schedule, ats_jobs.shift,
                        ats_jobs.description, ats_jobs.qualifications, ats_jobs.num_openings,
                        ats_jobs.years_exp, ats_jobs.job_url, ats_jobs.apply_url,
                        ats_jobs.outbound_link_url, ats_jobs.apply_phone,
                        ats_jobs.date_posted, ats_jobs.created_ts, ats_jobs.modified_ts,
                        ats_jobs.editable, ats_jobs.modal_style, ats_jobs.hide_apply
                        (3959 * ACOS(
                             COS(RADIANS(%1$s))
                             * COS(RADIANS(latitude))
                             * COS(RADIANS(longitude) - RADIANS(%2$s))
                             + SIN(RADIANS(%1$s))
                             * SIN(RADIANS(latitude))
                         )) AS distance
                        FROM zipcodes
                        INNER JOIN ats_jobs ON (zipcodes.postal_code = ats_jobs.zipcode)
                        WHERE ats_jobs.created_by = %3$d
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        HAVING distance <= %4$d
                        ORDER BY `%5$s` %6$s
                        LIMIT %7$d, %8$d',
                        $this->_db->quote($result['latitude']),
                        $this->_db->quote($result['longitude']),
                        $user_id,
                        $radius,
                        $orderBy,
                        $sortOrder,
                        $offset,
                        $perPage);

        $results = $this->_db->query($sql)->fetchAll();
        if (!empty($results)) {
            foreach ($results as $k => $row) {
                $results[$k]['url'] = $this->_domain . 'job/details/' . Clean::uristub($row['location']) . '/' . Clean::uristub($row['category']) . '/' . Clean::uristub($row['uristub']);
            }
        }

        // get number of results
        $numResults = count($results);
        // get total number of pages
        $totalPages = $totalResults > 0 && $totalResults > $perPage ? ceil($totalResults / $perPage) : 0;
        // check if we have another page
        $nextPage = $totalPages > $page ? $page + 1 : null;

        // revert order by
        if ($orderBy == 'name') {
            $orderBy = 'title';
        } else if ($orderBy == 'modified_ts') {
            $orderBy = 'last_modified';
        }

        return array(
            'results' => $results,
            'url' => $this->_domain . 'jobs/all/',
            'zipcode' => $zipcode,
            'radius' => $radius,
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'sortOrder' => $sortOrder,
            'nextPage' => $nextPage,
            'numResults' => $numResults,
            'totalResults' => $totalResults,
            'totalPages' => $totalPages
        );
    }


    /**
     * Retrieves all cities ordered for general use.
     *
     * @access  public
     * @param   int     $id
     * @param   int     $user_id
     * @return  array
     */
    public function getById($id, $user_id)
    {
        $sql = sprintf('SELECT id, job_id, uristub, company, name, location, city,
                        state, address, zipcode, category, department, schedule,
                        shift, description, qualifications, num_openings, years_exp,
                        job_url, apply_url, outbound_link_url, apply_phone,
                        date_posted, created_ts, modified_ts,
                        editable, modal_style, hide_apply
                        FROM ats_jobs
                        WHERE id = %d
                        AND created_by = %d
                        AND closed = 0
                        AND deleted = 0',
                        $id,
                        $user_id);

        $result = $this->_db->query($sql)->fetch();
        if (!empty($result)) {
            $result['url'] = $this->_domain . 'job/details/' . Clean::uristub($result['location']) . '/' . Clean::uristub($result['category']) . '/' . Clean::uristub($result['uristub']);
        }

        return $result;
    }

    /**
     * Retrieves all cities ordered for general use.
     *
     * @access  public
     * @param   int     $job_id
     * @param   int     $user_id
     * @return  array
     */
    public function getByJobId($job_id, $user_id)
    {
        $sql = sprintf('SELECT id, job_id, uristub, company, name, location, city,
                        state, address, zipcode, category, department, schedule,
                        shift, description, qualifications, num_openings, years_exp,
                        job_url, apply_url, outbound_link_url, apply_phone,
                        date_posted, created_ts, modified_ts,
                        editable, modal_style, hide_apply
                        FROM ats_jobs
                        WHERE job_id = %d
                        AND created_by = %d
                        AND closed = 0
                        AND deleted = 0',
                        $job_id,
                        $user_id);

        $result = $this->_db->query($sql)->fetch();
        if (!empty($result)) {
            $result['url'] = $this->_domain . 'job/details/' . Clean::uristub($result['location']) . '/' . Clean::uristub($result['category']) . '/' . Clean::uristub($result['uristub']);
        }

        return $result;
    }

    /**
     * Retrieves all cities ordered for general use.
     *
     * @access  public
     * @param   int     $id
     * @param   int     $user_id
     * @return  array
     */
    public function getByUriStub($uristub, $user_id)
    {
        $sql = sprintf('SELECT id, job_id, uristub, company, name, location, city,
                        state, address, zipcode, category, department, schedule,
                        shift, description, qualifications, num_openings, years_exp,
                        job_url, apply_url, outbound_link_url, apply_phone,
                        date_posted, created_ts, modified_ts,
                        editable, modal_style, hide_apply
                        FROM ats_jobs
                        WHERE uristub = "%s"
                        AND created_by = %d
                        AND closed = 0
                        AND deleted = 0',
                        $uristub,
                        $user_id);

        $result = $this->_db->query($sql)->fetch();
        if (!empty($result)) {
            $result['url'] = $this->_domain . 'job/details/' . Clean::uristub($result['location']) . '/' . Clean::uristub($result['category']) . '/' . Clean::uristub($result['uristub']);
        }

        return $result;
    }

    /**
     * Retrieves a count of all city counts.
     *
     * @access  public
     * @param   int             $user_id
     * @param   string|array    $city
     * @param   string|array    $state
     * @param   string|array    $location
     * @param   string|array    $category
     * @param   string|array    $schedule
     * @return  mixed
     */
    public function getCountByUserId(
        $user_id,
        $city = NULL,
        $state = NULL,
        $location = NULL,
        $category = NULL,
        $schedule = NULL
    )
    {
        // begin generation of AND params
        $andClause = $this->_generateFilterSql($location, $city, $state, $category, $schedule);

        $sql = sprintf('SELECT COUNT(1) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        %s
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY created_by',
                        $user_id,
                        $andClause);

        $result = $this->_db->query($sql)->fetch();

        // get number of results
        $numResults = !empty($result) ? 1 : 0;

        return array(
            'url' => $this->_domain . 'jobs/all/',
            'count' => !empty($result['count']) ? (int) $result['count'] : 0,
            'city' => $city,
            'state' => $state,
            'location' => $location,
            'category' => $category,
            'schedule' => $schedule
        );
    }

    /**
     * Search for matches with loads of filtering.
     *
     * @access  public
     * @param   int             $user_id
     * @param   string|array    $city
     * @param   string|array    $state
     * @param   string|array    $location
     * @param   string|array    $category
     * @param   string|array    $schedule
     * @param   int             $page
     * @param   int             $perPage
     * @param   string          $orderBy
     * @param   string          $sortOrder
     * @return  mixed
     */
    public function searchByUserId(
        $user_id,
        $city = NULL,
        $state = NULL,
        $location = NULL,
        $category = NULL,
        $schedule = NULL,
        $name = NULL,
        $page = 0,
        $perPage = 25,
        $orderBy = 'title',
        $sortOrder = 'ASC'
    ) {
        // begin generation of AND params
        $andClause = $this->_generateFilterSql($location, $city, $state, $category, $schedule, $name);

        // tiny fix for an odd mapping
        if ($orderBy == 'title') {
            $orderBy = 'name';
        } else if ($orderBy == 'last_modified') {
            $orderBy = 'modified_ts';
        }

        // retrieve search results count
        $sql = sprintf('SELECT COUNT(1) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        %s
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY created_by',
                        $user_id,
                        $andClause);

        $resultCount = $this->_db->query($sql)->fetch();
        if (empty($resultCount) || $resultCount['count'] == 0) {
            // revert order by
            if ($orderBy == 'name') {
                $orderBy = 'title';
            } else if ($orderBy == 'modified_ts') {
                $orderBy = 'last_modified';
            }

            return array(
                'results' => array(),
                'url' => $this->_domain . 'jobs/all/',
                'city' => $city,
                'state' => $state,
                'location' => $location,
                'category' => $category,
                'schedule' => $schedule,
                'name' => $name,
                'page' => $page,
                'perPage' => $perPage,
                'orderBy' => $orderBy,
                'sortOrder' => $sortOrder,
                'nextPage' => null,
                'numResults' => 0,
                'totalResults' => 0,
                'totalPages' => 0
            );
        }

        $totalResults = (int) $resultCount['count'];

        // calculate the offset
        $offset = $this->getPaginationOffset($page, $perPage);

        // retrieve jobs matching search criteria
        $sql = sprintf('SELECT id, job_id, uristub, company, name, location, city,
                        state, address, zipcode, category, department, schedule,
                        shift, description, qualifications, num_openings, years_exp,
                        job_url, apply_url, outbound_link_url, apply_phone,
                        date_posted, created_ts, modified_ts,
                        editable, modal_style, hide_apply
                        FROM ats_jobs
                        WHERE created_by = %d
                        %s
                        AND closed = 0
                        AND deleted = 0
                        ORDER BY `%s` %s
                        LIMIT %d,%d',
                        $user_id,
                        $andClause,
                        $orderBy,
                        $sortOrder,
                        $offset,
                        $perPage);


        $results = $this->_db->query($sql)->fetchAll();
        if (!empty($results)) {
            foreach ($results as $k => $row) {
                $results[$k]['url'] = $this->_domain . 'job/details/' . Clean::uristub($row['location']) . '/' . Clean::uristub($row['category']) . '/' . Clean::uristub($row['uristub']);
            }
        }

        // get number of results
        $numResults = count($results);
        // get total number of pages
        $totalPages = $totalResults > 0 && $totalResults > $perPage ? ceil($totalResults / $perPage) : 0;
        // check if we have another page
        $nextPage = $totalPages > $page ? $page + 1 : null;

        // revert order by
        if ($orderBy == 'name') {
            $orderBy = 'title';
        } else if ($orderBy == 'modified_ts') {
            $orderBy = 'last_modified';
        }

        return array(
            'results' => $results,
            'url' => $this->_domain . 'jobs/all/',
            'city' => $city,
            'state' => $state,
            'location' => $location,
            'category' => $category,
            'schedule' => $schedule,
            'name' => $name,
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => $orderBy,
            'sortOrder' => $sortOrder,
            'nextPage' => $nextPage,
            'numResults' => $numResults,
            'totalResults' => $totalResults,
            'totalPages' => $totalPages
        );
    }

    /**
     * Generates the filter SQL for searching and counting jobs based on
     * a given set of filters.
     *
     * @access  public
     * @param
     * @return  string
     */
    protected function _generateFilterSql($location, $city, $state, $category, $schedule, $name)
    {
        // begin generation of AND params
        $andClause = array();

        if ($location) {
            if (is_array($location)) {
                $locations = array();
                foreach ($location as $l) {
                    $locations[] = $this->_db->quote($l);
                }
                $andClause[] = sprintf('AND location IN (%s)', implode(',', $locations));
            } else {
                // fix location if necessary
                $andClause[] = sprintf('AND location = %s', $this->_db->quote($location));
            }
        } else {
            if ($city) {
                if (is_array($city)) {
                    $cities = array();
                    foreach ($city as $c) {
                        $cities[] = $this->_db->quote($c);
                    }
                    $andClause[] = sprintf('AND city IN (%s)', implode(',', $cities));
                } else {
                    $andClause[] = sprintf('AND city = %s', $this->_db->quote($city));
                }
            }
            if ($state) {
                if (is_array($state)) {
                    $states = array();
                    foreach ($state as $s) {
                        $states[] = $this->_db->quote($s);
                    }
                    $andClause[] = sprintf('AND state IN (%s)', implode(',', $states));
                } else {
                    $andClause[] = sprintf('AND state = %s', $this->_db->quote($state));
                }
            }
        }
        if ($category) {
            if (is_array($category)) {
                $categories = array();
                foreach ($category as $c) {
                    $categories[] = $this->_db->quote($c);
                }
                $andClause[] = sprintf('AND category IN (%s)', implode(',', $categories));
            } else {
                $andClause[] = sprintf('AND category = %s', $this->_db->quote($category));
            }
        }
        if ($schedule) {
            if (is_array($schedule)) {
                $schedules = array();
                foreach ($schedule as $s) {
                    $schedules[] = $this->_db->quote($s);
                }
                $andClause[] = sprintf('AND schedule IN (%s)', implode(',', $schedules));
            } else {
                $andClause[] = sprintf('AND schedule = %s', $this->_db->quote($schedule));
            }
        }
         if ($name) {
            if (is_array($name)) {
                $names = array();
                foreach ($names as $n) {
                    $names[] = $this->_db->quote($n);
                }
                //don't think we'll hit this condition
                //$andClause[] = sprintf('AND name LIKE %(%n)%', implode(',', $names));
            } else {
                $andClause[] = 'AND name LIKE "%' . $name . '%"';
            }
        }
        return !empty($andClause) ? implode(' ', $andClause) : '';
    }

}

