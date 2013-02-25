<?php
abstract class Skookum_Model
{
    protected $_db;
    protected $_container;
    protected $_validator;

    public function __construct()
    {
        $this->_db = $this->getService('dbAdapter');
    }

    /**
     * get a service by name
     *
     * @access public
     * @param String $name
     * @return mixed
     */
    public function getService($name)
    {
        return $this->getServices()->offsetGet($name);
    }

    /**
     * get the service container
     *
     * @access public
     * @param ArrayObject $container an object containing references to various services. db, config etc..
     * @return void
     */
    public function setServices(ArrayObject $container)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * get the service container
     *
     * @access public
     * @return void
     */
    public function getServices()
    {
        if (!$this->_container) {
            $this->_container = Zend_Registry::getInstance();
        }

        return $this->_container;
    }

    /**
     * return a full result set as an SplFixedArray
     *
     * @access protected
     * @param Object $result a result form a query
     * @param Closure $filter a filter callback to process each row
     * @return SplFixedArray
     */
    protected function getResultSet($result, Closure $filter = null)
    {
        // may need to handle different result types. check the instances befor processing
        if ($result instanceof Zend_Db_Statement) {
            $res = new SplFixedArray($result->rowCount());

            $i = 0;
            while($row = $result->fetchObject()) {

                if ($filter) {
                    $row = $filter($row);
                }

                $res->offsetSet($i, $row);
                $i++;
            }

            return $res;
        }

        return new SplFixedArray(0);
    }

    /**
     * Retrieve a single row given a particular statement. Returns false if
     * no results were returned.
     *
     * $fetchMode supports the following:
     * Zend_Db::FETCH_ASSOC, Zend_Db::FETCH_NUM, Zend_Db::FETCH_OBJ
     *
     * @access  protected
     * @param   Zend_Db_Statement   $stmt
     * @param   int                 $fetchMode [Zend_Db::FETCH_ASSOC|Zend_Db::FETCH_NUM]
     * @return  mixed
     */
    protected function fetchRow($stmt, $fetchMode = Zend_Db::FETCH_ASSOC)
    {
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch($fetchMode);
        }
        return array();
    }

    /**
     * Set the validator.
     *
     * @access  public
     * @param   Skookum_Form_Validator  $validator
     * @return  void
     */
    public function setValidator(Skookum_Form_Validator $validator)
    {
        $this->_validator = $validator;
    }

    /**
     * Get the validator.
     *
     * @access  public
     * @param   mixed   $data
     * @return  void
     */
    public function getValidator($data = NULL)
    {
        if (!$this->_validator) $this->_validator = new Skookum_Form_Validator($data);
        else if (!empty($data)) $this->_validator->setData($data);
        return $this->_validator;
    }

    /**
     * Generates a uristub of maximum specified length.
     *
     * @access  private
     * @param   string  $tableName      The name of the table to query
     * @param   string  $tableField     The table column to check
     * @param   string  $uristub        The initial input string (page title) to clean
     * @param   int     $length         The maximum allowable length for the clean url
     * @param   mixed   $iteration      The current iteration, when duplicates found
     * @return  string
     */
    protected function _generateUristub($tableName, $tableField, $uristub, $length = 30, $iteration = NULL)
    {
        // begin uristub generation on first iteration
        if (is_null($iteration)) {
            $uristub = $this->_simpleUristub($uristub, $length);
        }

        // if we have an iteration, add to the uristub
        $uristubToCheck = !empty($iteration) ? $uristub . $iteration : $uristub;

        // check if the uristub exists
        $sql = sprintf('SELECT 1 FROM `%s` WHERE `%s` = %s',
                       $tableName,
                       $tableField,
                       $this->_db->quote($uristubToCheck));

        try {
            $result = $this->_db->query($sql)->fetch();
            if ($result === FALSE) {
                return $uristubToCheck;
            }
        } catch (Exception $e) {
            // fatal
            die($e->getMessage());
        }

        // increment iteration before trying again
        $iteration = (is_null($iteration)) ? 1 : ++$iteration;
        return $this->_generateUristub($tableName, $tableField, $uristub, $length, $iteration);
    }

    /**
     * Generates a simple uristub without all of the database fuss.
     *
     * @access  protected
     * @param   string  $uristub
     * @param   int     $length
     * @return  string
     */
    protected function _simpleUristub($uristub, $length = 30)
    {
        // set the locale, just once
        setlocale(LC_ALL, 'en_US.UTF8');

        // clean the uristub
        $uristub = iconv('UTF-8', 'ASCII//TRANSLIT', $uristub);
        $uristub = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $uristub);
        $uristub = preg_replace("/[\/_|+ -]+/", '-', $uristub);
        $uristub = strtolower(trim($uristub, '-'));

        // ensure uristub is less than length
        if (strlen($uristub) > $length) {
            // get char at chopped position
            $char = $uristub[$length-1];
            // quick chop (leave room for 9 iterations)
            $uristub = substr($uristub, 0, $length - 1);

            // if we chopped mid word
            if ($char != '-') {
                $pos = strrpos($uristub, '-');
                if ($pos !== FALSE) {
                    $uristub = substr($uristub, 0, $pos);
                }
            }
        }

        return $uristub;
    }

}
