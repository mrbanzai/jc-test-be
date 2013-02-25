<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-webat this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: DbTable.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * Zend_Session_SaveHandler_DbTable
 *
 * @category   Zend
 * @package    Zend_Session
 * @subpackage SaveHandler
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Skookum_Session_SaveHandler_DbAdapter implements Zend_Session_SaveHandler_Interface
{
    const TABLE_NAME                            = 'tableName';
    const PRIMARY_KEY                           = 'primary';
    const PRIMARY_ASSIGNMENT                    = 'primaryAssignment';
    const PRIMARY_ASSIGNMENT_SESSION_SAVE_PATH  = 'sessionSavePath';
    const PRIMARY_ASSIGNMENT_SESSION_NAME       = 'sessionName';
    const PRIMARY_ASSIGNMENT_SESSION_ID         = 'sessionId';

    const MODIFIED_COLUMN   = 'modifiedColumn';
    const LIFETIME_COLUMN   = 'lifetimeColumn';
    const DATA_COLUMN       = 'dataColumn';

    const LIFETIME          = 'lifetime';
    const OVERRIDE_LIFETIME = 'overrideLifetime';

    /**
     * Session table primary key value assignment
     *
     * @var array
     */
    protected $_primaryAssignment = null;

    /**
     * The database table name
     *
     * @var string
     */
    protected $_tableName = null;

    /**
     * Session table primary key column name
     *
     * @var string
     */
    protected $_primaryColumn = null;

    /**
     * Session table last modification time column
     *
     * @var string
     */
    protected $_modifiedColumn = null;

    /**
     * Session table lifetime column
     *
     * @var string
     */
    protected $_lifetimeColumn = null;

    /**
     * Session table data column
     *
     * @var string
     */
    protected $_dataColumn = null;

    /**
     * Session lifetime
     *
     * @var int
     */
    protected $_lifetime = false;

    /**
     * Whether or not the lifetime of an existing session should be overridden
     *
     * @var boolean
     */
    protected $_overrideLifetime = false;

    /**
     * Session save path
     *
     * @var string
     */
    protected $_sessionSavePath;

    /**
     * Session name
     *
     * @var string
     */
    protected $_sessionName;
    
    /**
     * The database adapter.
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * Constructor
     *
     * $config is an instance of Zend_Config or an array of key/value pairs containing configuration options for
     * Zend_Session_SaveHandler_DbTable and Zend_Db_Table_Abstract. These are the configuration options for
     * Zend_Session_SaveHandler_DbTable:
     *
     * primaryAssignment => (string|array) Session table primary key value assignment
     *      (optional; default: 1 => sessionId) You have to assign a value to each primary key of your session table.
     *      The value of this configuration option is either a string if you have only one primary key or an array if
     *      you have multiple primary keys. The array consists of numeric keys starting at 1 and string values. There
     *      are some values which will be replaced by session information:
     *
     *      sessionId       => The id of the current session
     *      sessionName     => The name of the current session
     *      sessionSavePath => The save path of the current session
     *
     *      NOTE: One of your assignments MUST contain 'sessionId' as value!
     *
     * modifiedColumn    => (string) Session table last modification time column
     * lifetimeColumn    => (string) Session table lifetime column
     * dataColumn        => (string) Session table data column
     * lifetime          => (integer) Session lifetime (optional; default: ini_get('session.gc_maxlifetime'))
     * overrideLifetime  => (boolean) Whether or not the lifetime of an existing session should be overridden
     *      (optional; default: false)
     *
     * @param   Zend_Db_Adapter $dbAdapter
     * @param   Zend_Config|array $config      User-provided configuration
     * @return  void
     * @throws  Zend_Session_SaveHandler_Exception
     */
    public function __construct($dbAdapter, $config)
    {
        if (!$dbAdapter instanceof Zend_Db_Adapter_Abstract) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception('$dbAdapter must be an instance of Zend_Db_Adapter_Abstract.');
        } else {
            // save the adapter
            $this->_db = $dbAdapter;
        }
        
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                '$config must be an instance of Zend_Config or array of key/value pairs containing '
              . 'configuration options for Zend_Session_SaveHandler_DbTable and Zend_Db_Table_Abstract.');
        }

        foreach ($config as $key => $value) {
            do {
                switch ($key) {
                    case self::TABLE_NAME:
                        $this->_tableName = $value;
                        break;
                    case self::PRIMARY_KEY:
                        $this->_primaryColumn = $value;
                        break;
                    case self::PRIMARY_ASSIGNMENT:
                        $this->_primaryAssignment = $value;
                        break;
                    case self::MODIFIED_COLUMN:
                        $this->_modifiedColumn = (string) $value;
                        break;
                    case self::LIFETIME_COLUMN:
                        $this->_lifetimeColumn = (string) $value;
                        break;
                    case self::DATA_COLUMN:
                        $this->_dataColumn = (string) $value;
                        break;
                    case self::LIFETIME:
                        $this->setLifetime($value);
                        break;
                    case self::OVERRIDE_LIFETIME:
                        $this->setOverrideLifetime($value);
                        break;
                    default:
                        // unrecognized options passed to parent::__construct()
                        break 2;
                }
                unset($config[$key]);
            } while (false);
        }
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        Zend_Session::writeClose();
    }

    /**
     * Set session lifetime and optional whether or not the lifetime of an existing session should be overridden
     *
     * $lifetime === false resets lifetime to session.gc_maxlifetime
     *
     * @param int $lifetime
     * @param boolean $overrideLifetime (optional)
     * @return Zend_Session_SaveHandler_DbTable
     */
    public function setLifetime($lifetime, $overrideLifetime = null)
    {
        if ($lifetime < 0) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';
            throw new Zend_Session_SaveHandler_Exception();
        } else if (empty($lifetime)) {
            $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
        } else {
            $this->_lifetime = (int) $lifetime;
        }

        if ($overrideLifetime != null) {
            $this->setOverrideLifetime($overrideLifetime);
        }

        return $this;
    }

    /**
     * Retrieve session lifetime
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * Set whether or not the lifetime of an existing session should be overridden
     *
     * @param boolean $overrideLifetime
     * @return Zend_Session_SaveHandler_DbTable
     */
    public function setOverrideLifetime($overrideLifetime)
    {
        $this->_overrideLifetime = (boolean) $overrideLifetime;

        return $this;
    }

    /**
     * Retrieve whether or not the lifetime of an existing session should be overridden
     *
     * @return boolean
     */
    public function getOverrideLifetime()
    {
        return $this->_overrideLifetime;
    }

    /**
     * Open Session
     *
     * @param string $save_path
     * @param string $name
     * @return boolean
     */
    public function open($save_path, $name)
    {
        $this->_sessionSavePath = $save_path;
        $this->_sessionName     = $name;

        return true;
    }

    /**
     * Close session
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $return = '';

        $sql = sprintf('SELECT * FROM %s WHERE %s = %s',
                       $this->_tableName,
                       $this->_primaryColumn,
                       $this->_db->quote($id));

        $row = $this->_db->query($sql)->fetch();
        if ($row) {
            if ($this->_getExpirationTime($row) > time()) {
                $return = $row[$this->_dataColumn];
            } else {
                $this->destroy($id);
            }
        }

        return $return;
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $return = false;

        $sql = sprintf('SELECT * FROM %s WHERE %s = %s',
                       $this->_tableName,
                       $this->_primaryColumn,
                       $this->_db->quote($id));
        
        $row = $this->_db->query($sql)->fetch();
        if ($row) {
            
            $sql = sprintf('UPDATE %s SET
                            %s = %d,
                            %s = %s,
                            %s = %s
                            WHERE %s = %s',
                            $this->_tableName,
                            $this->_modifiedColumn,
                            time(),
                            $this->_dataColumn,
                            $this->_db->quote((string) $data),
                            $this->_lifetimeColumn,
                            $this->_getLifetime($row),
                            $this->_primaryColumn,
                            $this->_db->quote($id));

            if ($this->_db->exec($sql)) {
                $return = true;
            }
            
            /*
            $data[$this->_lifetimeColumn] = $this->_getLifetime($row);
            $where = sprintf('%s = %s', $this->_primaryColumn, $this->_db->quote($id));
            if ($this->_db->update($this->_tableName, $data, $where)) {
                $return = true;
            }
            */
            
        } else {
            
            $sql = sprintf('INSERT INTO %s SET
                            %s = %s,
                            %s = %d,
                            %s = %s,
                            %s = %d',
                            $this->_tableName,
                            $this->_primaryColumn,
                            $this->_db->quote($id),
                            $this->_modifiedColumn,
                            time(),
                            $this->_dataColumn,
                            $this->_db->quote((string) $data),
                            $this->_lifetimeColumn,
                            $this->_lifetime);
            
            if ($this->_db->exec($sql)) {
                $return = true;
            }
            
            /*
            $data[$this->_lifetimeColumn] = $this->_lifetime;
            if ($this->_db->insert($this->_tableName, array_merge(array($this->_primaryColumn => $id), $data))) {
                $return = true;
            }
            */
        }

        return $return;
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {
        $return = false;
        
        $sql = sprintf('DELETE FROM %s WHERE %s = %s',
                        $this->_tableName,
                        $this->_primaryColumn,
                        $this->_db->quote($id));
        
        if ($this->_db->exec($sql)) {
            $return = true;
        }
        
        return $return;
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        $sql = sprintf('DELETE FROM %s WHERE %s + %s < %d',
                        $this->_tableName,
                        $this->_modifiedColumn,
                        $this->_lifetimeColumn,
                        time());
        
        $this->_db->query($sql);
        
        return true;
    }

    /**
     * Calls other protected methods for individual setup tasks and requirement checks
     *
     * @return void
     */
    protected function _setup()
    {
        $this->setLifetime($this->_lifetime);
        $this->_checkRequiredColumns();
    }

    /**
     * Check for required session table columns
     *
     * @return void
     * @throws Zend_Session_SaveHandler_Exception
     */
    protected function _checkRequiredColumns()
    {
        if ($this->_modifiedColumn === null) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::MODIFIED_COLUMN . "' which names the "
              . "session table last modification time column.");
        } else if ($this->_lifetimeColumn === null) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::LIFETIME_COLUMN . "' which names the "
              . "session table lifetime column.");
        } else if ($this->_dataColumn === null) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                "Configuration must define '" . self::DATA_COLUMN . "' which names the "
              . "session table data column.");
        }
    }

    /**
     * Retrieve session lifetime considering Zend_Session_SaveHandler_DbTable::OVERRIDE_LIFETIME
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @return int
     */
    protected function _getLifetime(array $row)
    {
        $return = $this->_lifetime;

        if (!$this->_overrideLifetime) {
            $return = (int) $row[$this->_lifetimeColumn];
        }

        return $return;
    }

    /**
     * Retrieve session expiration time
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @return int
     */
    protected function _getExpirationTime(array $row)
    {
        return (int) $row[$this->_modifiedColumn] + $this->_getLifetime($row);
    }
}
