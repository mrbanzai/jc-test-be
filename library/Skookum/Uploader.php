<?php
class Skookum_Uploader extends Zend_Form
{
    protected $fileName = "";
    
    public function __construct($options = null)
    {
        if ($options && array_key_exists('fileName', (array) $options)) {
            $this->fileName = $options['fileName'];
            unset($options['fileName']);
        }
        
        parent::__construct($options);
    }
    
    public function init()
    {
        $file = new Zend_Form_Element_File($this->fileName ?: 'file');
        $file->setRequired(true);

        $this->addElement($file);
    }
    
    /**
     * set the upload directory of the file
     *
     * @access public
     * @param string $path
     * @return $this
     */
    public function setFileDestination($path)
    {
        $this->getElement($this->fileName)->setDestination($path);
        return $this;
    }
    
    /**
     * set the file to be required or not
     *
     * @access public
     * @param boolean $val
     * @return $this
     */
    public function setFileRequired($val)
    {
        $this->getElement($this->fileName)->setRequired($val);
        return $this;
    }
    
    /**
     * gets the internal Zend_Form_Element_File object
     *
     * @access public
     * @return Zend_Form_Element_File
     */
    public function getFile()
    {
        return $this->getElement($this->fileName);
    }
    
    /**
     * determines if the file upload is valid
     */
    public function isValid($data)
    {
        $isvalid = parent::isValid($data);
        $this->getFile()->receive();
        return $isvalid;
    }
}