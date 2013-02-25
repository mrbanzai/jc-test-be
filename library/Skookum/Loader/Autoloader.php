<?php
class Skookum_Loader_Autoloader implements Zend_Loader_Autoloader_Interface
{
    protected $_map = array();

    public function __construct(array $map)
    {
        $this->_map = $map;
    }

    /**
     * autoloader attempts to load a file from the map
     *
     * @access public
     * @param string $class
     * @return void
     */
    public function autoload($class)
    {
        if(isset($this->_map[$class])) {
            require_once $this->_map[$class];
        }
    }

}