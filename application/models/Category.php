<?php
class Category extends Skookum_Model
{

    /**
     * Retrieves all categories ordered for use in a dropdown.
     *
     * @access  public
     * @param   string  $subdomain
     * @param   mixed   $category
     * @return  string
     */
    public function getAllForDropdown($subdomain, $category = NULL)
    {
        $return = '<option value=""></option>';

        $sql = sprintf('SELECT DISTINCT ats_jobs.category
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.category ASC',
                        $this->_db->quote($subdomain));

        $result = $this->_db->query($sql)->fetchAll();
        if ($result) {
            foreach ($result as $r) {
                $val = Clean::uristub($r['category']);
                $return .= '<option value="' . Clean::uristub($r['category']) . '"';
                $return .= (!empty($category) && $category == $val) ? ' selected="selected">' : '>';
                $return .= Clean::xss($r['category']);
                $return .= '</option>';
            }
        }

        return $return;
    }

    /**
     * Retrieves all categories ordered for general use.
     *
     * @access  public
     * @param   string  $subdomain
     * @return  array
     */
    public function getAllBySubdomain($subdomain)
    {
        $sql = sprintf('SELECT DISTINCT ats_jobs.category
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.category ASC',
                        $this->_db->quote($subdomain));

        return $this->_db->query($sql)->fetchAll();
    }

    /**
     * Retrieve the most popular categories.
     *
     * @access  public
     * @param   int     $limit
     * @param   string  $subdomain
     * @return  array
     */
    public function getMostPopular($limit, $subdomain)
    {
        $sql = sprintf('SELECT ats_jobs.category, COUNT(1) AS total
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        GROUP BY ats_jobs.category
                        ORDER BY total DESC
                        LIMIT %d',
                        $this->_db->quote($subdomain),
                        $limit);

        return $this->_db->query($sql)->fetchAll();
    }

}