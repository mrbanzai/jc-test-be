<?php
class City extends Skookum_Model
{

    /**
     * Retrieves all cities ordered for general use.
     *
     * @access  public
     * @param   string  $subdomain
     * @return  array
     */
    public function getAllBySubdomain($subdomain)
    {
        $sql = sprintf('SELECT DISTINCT ats_jobs.city
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.city ASC',
                        $this->_db->quote($subdomain));

        $result = $this->_db->query($sql)->fetchAll();
        return $this->_cleanCities($result);
    }

    /**
     * Retrieve the most popular cities.
     *
     * @access  public
     * @param   int     $limit
     * @param   string  $subdomain
     * @return  array
     */
    public function getMostPopular($limit, $subdomain)
    {
        $sql = sprintf('SELECT ats_jobs.city, COUNT(1) AS total
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        GROUP BY ats_jobs.city
                        ORDER BY total DESC
                        LIMIT %d',
                        $this->_db->quote($subdomain),
                        $limit);

        return $this->_db->query($sql)->fetchAll();
    }

}
