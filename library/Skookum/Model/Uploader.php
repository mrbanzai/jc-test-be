<?php
abstract class Skookum_Model_Uploader extends Skookum_Model
{
    protected $_uploader;
    protected $_config;
    
    public function setUploader(Skookum_Uploader $uploader)
    {
        $this->_uploader = $uploader;
    }
    
    public function getUploader()
    {
        if(!$this->_uploader) $this->_uploader = new Skookum_Uploader();
        return $this->_uploader;
    }
    
    abstract public function handle(array $files);
}