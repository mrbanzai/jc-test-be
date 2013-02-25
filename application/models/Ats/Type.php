<?php
class Ats_Type extends Skookum_Model
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
		$result = $this->_db->query('SELECT * FROM ats_type')->fetchAll();
		if ($result) {
			foreach ($result as $r) {
				$return[$r['name']] = $r;
			}
		}
        return $return;
	}

	/**
	 * Retrieve all user roles.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAllForDropdown()
    {
		$return = array();
		$result = $this->_db->query('SELECT * FROM ats_type')->fetchAll();
		if ($result) {
			foreach ($result as $r) {
				$return[$r['id']] = $r['name'];
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
        $sql = sprintf('SELECT * FROM ats_type WHERE id = %d', $id);
        return $this->_db->query($sql)->fetch();
    }

}
