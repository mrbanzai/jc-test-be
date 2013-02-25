<?php
class State extends Skookum_Model
{

    /**
     * Retrieves all states ordered for general use.
     *
     * @access  public
     * @param   string  $subdomain
     * @return  array
     */
    public function getAllBySubdomain($subdomain)
    {
        $sql = sprintf('SELECT DISTINCT ats_jobs.state
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.state ASC',
                        $this->_db->quote($subdomain));

        $result = $this->_db->query($sql)->fetchAll();
        return $this->_cleanCities($result);
    }

    /**
     * Retrieve the most popular states.
     *
     * @access  public
     * @param   int     $limit
     * @param   string  $subdomain
     * @return  array
     */
    public function getMostPopular($limit, $subdomain)
    {
        $sql = sprintf('SELECT ats_jobs.state, COUNT(1) AS total
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        GROUP BY ats_jobs.state
                        ORDER BY total DESC
                        LIMIT %d',
                        $this->_db->quote($subdomain),
                        $limit);

        return $this->_db->query($sql)->fetchAll();
    }

}
