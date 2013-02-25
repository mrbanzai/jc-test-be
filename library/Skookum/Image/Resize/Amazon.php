<?php
class Skookum_Image_Resize_Amazon extends Image_Resize
{
    public function resize($basedir, $hash, array $files)
    {
        $images = parent::resize($basedir, $hash, $files);
        
        $opts = $this->getOptions();
        
        $s3 = new Zend_Service_Amazon_S3($opts['aws']['access_key'],
                                         $opts['aws']['secret_key']);
        
        foreach($images as $sizes) {
            foreach($sizes as $size) {
                
                $meta = array(Zend_Service_Amazon_S3::S3_ACL_HEADER =>
                                Zend_Service_Amazon_S3::S3_ACL_PUBLIC_READ);
                

                $bucket = $opts['aws']['s3']['photo_bucket'];
                $object = $bucket . str_replace($opts['base_path'],"", $size['path']);
                
                $s3->putObject($object,
                               file_get_contents($size['path']),
                               $meta);
                
                // delete the original image
                unlink($size['path']);
            }
        }

        return $images;
    }
}