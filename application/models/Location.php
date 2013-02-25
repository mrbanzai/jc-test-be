<?php
class Location extends Skookum_Model
{

    protected $_stateMap = array(
        'AL'=>"Alabama",
        'AK'=>"Alaska",
        'AZ'=>"Arizona",
        'AR'=>"Arkansas",
        'CA'=>"California",
        'CO'=>"Colorado",
        'CT'=>"Connecticut",
        'DE'=>"Delaware",
        'DC'=>"District Of Columbia",
        'FL'=>"Florida",
        'GA'=>"Georgia",
        'HI'=>"Hawaii",
        'ID'=>"Idaho",
        'IL'=>"Illinois",
        'IN'=>"Indiana",
        'IA'=>"Iowa",
        'KS'=>"Kansas",
        'KY'=>"Kentucky",
        'LA'=>"Louisiana",
        'ME'=>"Maine",
        'MD'=>"Maryland",
        'MA'=>"Massachusetts",
        'MI'=>"Michigan",
        'MN'=>"Minnesota",
        'MS'=>"Mississippi",
        'MO'=>"Missouri",
        'MT'=>"Montana",
        'NE'=>"Nebraska",
        'NV'=>"Nevada",
        'NH'=>"New Hampshire",
        'NJ'=>"New Jersey",
        'NM'=>"New Mexico",
        'NY'=>"New York",
        'NC'=>"North Carolina",
        'ND'=>"North Dakota",
        'OH'=>"Ohio",
        'OK'=>"Oklahoma",
        'OR'=>"Oregon",
        'PA'=>"Pennsylvania",
        'RI'=>"Rhode Island",
        'SC'=>"South Carolina",
        'SD'=>"South Dakota",
        'TN'=>"Tennessee",
        'TX'=>"Texas",
        'UT'=>"Utah",
        'VT'=>"Vermont",
        'VA'=>"Virginia",
        'WA'=>"Washington",
        'WV'=>"West Virginia",
        'WI'=>"Wisconsin",
        'WY'=>"Wyoming"
    );

    /**
     * Retrieves all locations ordered for use in a dropdown.
     *
     * @access  public
     * @param   string  $subdomain
     * @param   mixed   $location
     * @return  string
     */
    public function getAllForDropdown($subdomain, $location = NULL)
    {
        $locations = array();
        $return = '<option value=""></option>';

        $sql = sprintf('SELECT DISTINCT ats_jobs.location
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.location ASC',
                        $this->_db->quote($subdomain));

        $result = $this->_db->query($sql)->fetchAll();
        if ($result) {
            foreach ($result as $r) {
                $val = Clean::uristub($r['location']);
                $clean = $this->_cleanLocation($r['location']);

                $locations[$clean] = '<option value="' . Clean::uristub($r['location']) . '"';
                $locations[$clean] .= (!empty($location) && $location == $val) ? ' selected="selected">' : '>';
                $locations[$clean] .= $clean;
                $locations[$clean] .= '</option>';
            }
        }

        // generate return string with sorted locations
        if (!empty($locations)) {
            // sort all locations
            ksort($locations);
            // add locations to return string
            $return .= implode("\n", $locations);
        }

        return $return;
    }

    /**
     * Retrieves all locations ordered for general use.
     *
     * @access  public
     * @param   string  $subdomain
     * @return  array
     */
    public function getAllBySubdomain($subdomain)
    {
        $sql = sprintf('SELECT DISTINCT ats_jobs.location
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        ORDER BY ats_jobs.location ASC',
                        $this->_db->quote($subdomain));

        $result = $this->_db->query($sql)->fetchAll();
        return $this->_cleanLocations($result);
    }

    /**
     * Retrieve the most popular locations.
     *
     * @access  public
     * @param   int     $limit
     * @param   string  $subdomain
     * @return  array
     */
    public function getMostPopular($limit, $subdomain)
    {
        $sql = sprintf('SELECT ats_jobs.location, COUNT(1) AS total
                        FROM users
                        INNER JOIN ats_jobs ON (users.id = ats_jobs.created_by)
                        WHERE users.subdomain = %s
                        AND ats_jobs.closed = 0
                        AND ats_jobs.deleted = 0
                        GROUP BY ats_jobs.location
                        ORDER BY total DESC
                        LIMIT %d',
                        $this->_db->quote($subdomain),
                        $limit);

        $result = $this->_db->query($sql)->fetchAll();
        return $this->_cleanLocations($result);
    }

    /**
     * Clean up an array of locations.
     *
     * @access  protected
     * @param   array   $locations
     * @return  array
     */
    protected function _cleanLocations($locations)
    {
        if ($locations) {
            foreach ($locations as $k => $l) {
                $locations[$k]['location_clean'] = $this->_cleanLocation($l['location']);
            }
        }
        return $locations;
    }


    /**
     * Attempt to clean a location string.
     *
     * @access  protected
     * @param   string  $location
     * @return  string
     */
    protected function _cleanLocation($location)
    {
        // remove references to "United States-" and fix ordering of city, state
        if (strpos($location, 'United States-') === 0 || strpos($location, 'US-') === 0) {
            $location = explode('-', $location);
            array_shift($location);
            if (count($location) > 1 && trim($location[1]) != '') {
                $location = Clean::xss($this->_replaceState($location[0])) . ' - ' . Clean::xss($location[1]);
            } else {
                $location = Clean::xss($this->_replaceState($location[0]));
            }
        }

        // replace long state names with short ones
        return $location;
    }

    /**
     * Replace long version of state names with short version.
     *
     * @access  public
     * @param   string  $location
     * @return  string
     */
    public function _replaceState($location)
    {
        foreach ($this->_stateMap as $short => $long) {
            if (strpos($location, $long) !== FALSE) {
                return preg_replace('#'.$long.'#i', $short, $location, 1);
                //return str_replace($long, $short, $location);
            }
        }
        return $location;
    }

}