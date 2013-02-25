<?php
class Skookum_View_Helper_ImagePath extends Zend_View_Helper_Abstract
{
    protected $opts;
    
    public function __construct()
    {
        $config = Zend_Registry::get('config')->get('application');
        $this->opts = $config->images;
    }
    
    public function imagePath($hash, $ext, $size = 'sm', array $params = array())
    {
        if (!empty($hash) && !empty($ext)) {
            
            $query = "";
            if (!empty($params)) {
                $query = '?' . http_build_query($params, '', '&amp;');
            }
            
            return sprintf('%s/%s/%s/%s_%s.%s%s',
                           trim($this->opts->domain, "/"),
                           trim($this->opts->webpath, "/"),
                           Skookum_Uploader_Hashpath::getPath($hash),
                           $hash,
                           $size,
                           trim($ext, "."),
                           $query);
        }
    }
}