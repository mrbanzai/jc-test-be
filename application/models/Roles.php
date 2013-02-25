<?php
class Roles extends Skookum_Model
{
    
	/**
	 * Retrieve all user roles.
	 *
	 * @access	public
	 * @return	array
	 */
    public function getAll() {
		return $this->_db->query('SELECT * FROM roles')->fetchAll();
	}
    
}