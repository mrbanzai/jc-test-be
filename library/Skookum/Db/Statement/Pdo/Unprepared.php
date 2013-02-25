<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Pdo.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Db_Statement
 */
require_once 'Zend/Db/Statement/Pdo.php';

/**
 * Proxy class to wrap a PDOStatement object.
 * Matches the interface of PDOStatement.  All methods simply proxy to the
 * matching method in PDOStatement.  PDOExceptions thrown by PDOStatement
 * are re-thrown as Zend_Db_Statement_Exception.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Skookum_Db_Statement_Pdo_Unprepared extends Zend_Db_Statement_Pdo
{
    
    // stores the database adapter
    protected $_adapter;
 
    // stores the fetch mode
    protected $_fetchMode;
    
    // stores the last ran sql
    protected $_sql;
    
    /**
     * Constructor for a statement.
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param mixed $sql Either a string or Zend_Db_Select.
     */
    public function __construct($adapter, $sql)
    {
        $this->_adapter = $adapter;
        if ($sql instanceof Zend_Db_Select) {
            $sql = $sql->assemble();
        }
        $this->_parseParameters($sql);
        $this->_prepare($sql);

        $this->_queryId = $this->_adapter->getProfiler()->queryStart($sql);
    }
    
    /**
     * Run the SQL query and store the result object.
     *
     * @param string $sql
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    /*
    protected function _prepare($sql)
    {
        $this->_sql = $sql;
    }
    */

    /**
     * Executes a prepared statement.
     *
     * @param array $params OPTIONAL Values to bind to parameter placeholders.
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    /*
    public function _execute(array $params = null)
    {
        try {
            if (!empty($params)) {
                $this->_stmt = $this->_adapter->getConnection()->prepare($this->_sql);
                $return = $this->_stmt->execute($params);
            } else {                
                $this->_stmt = $this->_adapter->getConnection()->query($this->_sql, $this->_fetchMode);
                error_log(print_r($this->_stmt, true));
            }
            return $this;
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
    */

    /**
     * Executes a prepared statement.
     *
     * @param array $params OPTIONAL Values to bind to parameter placeholders.
     * @return bool
     */
    public function execute(array $params = null)
    {
        return parent::execute($params);
    }

    /**
     * Set the default fetch mode for this statement.
     *
     * @param   int   $mode The fetch mode.
     * @return  bool
     * @throws  Zend_Db_Statement_Exception
     */
    public function setFetchMode($mode)
    {
        $this->_fetchMode = $mode;
    }
   
}