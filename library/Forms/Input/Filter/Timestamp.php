<?php
class Forms_Input_Filter_Timestamp implements Zend_Filter_Interface
{

    protected $_format = 'M d, Y g:i A';

    /**
     * Constructor
     *
     * @param string|array|Zend_Config $options OPTIONAL
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            $options = func_get_args();
            $temp    = array();
            if (!empty($options)) {
                $temp['type'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['casting'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['locale'] = array_shift($options);
            }

            $options = $temp;
        }

        if (array_key_exists('format', $options)) {
            $this->setFormat($options['format']);
        }
    }

    public function getFormat()
    {
        return $this->_format;
    }
    
    public function setFormat($format = null)
    {
        $this->_format = $format;
        return $this;
    }

    public function filter($value)
    {
        $ts = (is_numeric($value)) ? $value : strtotime($value);
        return date($this->getFormat(), $ts);
    }
}
