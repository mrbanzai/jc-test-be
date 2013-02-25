<?php
class Ats_Feed_Type extends Skookum_Model
{

	/**
	 * Retrieve all user roles.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll()
    {
		$return = array();
		$result = $this->_db->query('SELECT * FROM ats_feed_type')->fetchAll();
		if ($result) {
			foreach ($result as $r) {
				$return[$r['id']] = $r;
			}
		}
        return $return;
	}

    /**
     * Retrieve an ATS type by id.
     *
     * @access  public
     * @param   int     $id
     */
    public function getById($id)
    {
        $sql = sprintf('SELECT * FROM ats_feed_type WHERE id = %d', $id);
        return $this->_db->query($sql)->fetch();
    }

}
