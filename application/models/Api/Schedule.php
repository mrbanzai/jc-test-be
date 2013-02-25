<?php
class Api_Schedule extends Skookum_Api_Server_Model_Api
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
        $sql = sprintf('SELECT COUNT(DISTINCT schedule) AS `count`
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
        $orderBy = 'schedule',
        $sortOrder = 'ASC'
    )
    {
        $offset = $this->getPaginationOffset($page, $perPage);

        $sql = sprintf('SELECT schedule, COUNT(schedule) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY schedule
                        ORDER BY `%s` %s
                        LIMIT %d,%d',
                        $user_id,
                        $orderBy,
                        $sortOrder,
                        $offset,
                        $perPage);

        // get result(s)
        $results = $this->_db->query($sql)->fetchAll();
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
        $sql = sprintf('SELECT COUNT(DISTINCT schedule) AS `count`
                        FROM ats_jobs
                        WHERE created_by = %d
                        AND closed = 0
                        AND deleted = 0
                        GROUP BY created_by',
                        $user_id);

        $result = $this->_db->query($sql)->fetch();

        return array(
            'count' => !empty($result['count']) ? (int) $result['count'] : 0
        );
    }

}
