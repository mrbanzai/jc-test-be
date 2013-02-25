<?php
/**
 * Local Authentication class
 *
 * @author Jim Snodgrass
 * @version June 2009
 */
class Auth_Facebook
{

	/**
	 * Authenticate the email/password combination.
	 *
	 * @access	static public
	 * @return	Zend_Auth_Adapter_DbTable
	 */
	public static function setAuthAdapter($values)
	{
		// Setup DbTable adapter
        $adapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
        
        $adapter->setTableName('users')
                ->setIdentityColumn('fbuid')
                ->setCredentialColumn('fbuid');

        $adapter->setIdentity($values['uid']);
        $adapter->setCredential($values['uid']);
        $adapter->setCredentialTreatment('?');

		return $adapter;
	}

}