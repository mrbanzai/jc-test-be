<?php
class Api_City extends Skookum_Api_Server_Model_Api
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
        $sql = sprintf('SELECT COUNT(DISTINCT city) AS `count`
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
     * Retrieves all cities ordered for general use.
     *
     * @access  public
     * @param   int     $user_id
     * @param   mixed   $states
     * @param   int     $page
     * @param   int     $perPage
     * @param   string  $orderBy
     * @param   string  $sortOrder
     * @return  array
     */
    public function getAllByUserId(
        $user_id,
        $states = NULL,
        $page = 0,
        $perPage = 25,
        $orderBy = 'city',
        $sortOrder = 'ASC'
    )
    {
        $offset = $this->getPaginationOffset($page, $perPage);

        // check for return by states
        $andClause = array();
        if ($states) {
            if (is_array($states)) {
                $stateArr = array();
                foreach ($states as $s) {
                    $stateArr[] = $this->_db->quote($s);
                }
                $andClause[] = sprintf('AND state IN (%s)', implode(',', $stateArr));
            } else {
                $andClause[] = sprintf('AND state = %s', $this->_db->quote($states));
            }
        }
        $andClause = !empty($andClause) ? implode(' ', $andClause) : '';

        $sql = sprintf('SELECT city, state, location, COUNT(city) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        %s
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY city
                        ORDER BY `%s` %s
                        LIMIT %d,%d',
                        $user_id,
                        $andClause,
                        $orderBy,
                        $sortOrder,
                        $offset,
                        $perPage);

        // get result(s)
        $results = $this->_db->query($sql)->fetchAll();
        if (!empty($results)) {
            foreach ($results as $k => $row) {
                $results[$k]['url'] = $this->_domain . 'jobs/location/' . Clean::uristub($row['location']);
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
            'url' => $this->_domain . 'jobs/city/',
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
     * Retrieves a count of all city counts.
     *
     * @access  public
     * @param   int     $user_id
     * @return  array
     */
    public function getCountByUserId($user_id)
    {
        $sql = sprintf('SELECT COUNT(DISTINCT city) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY created_by',
                        $user_id);

        $result = $this->_db->query($sql)->fetch();

        return array(
            'url' => $this->_domain . 'jobs/city/',
            'count' => !empty($result['count']) ? (int) $result['count'] : 0
        );
    }

}
