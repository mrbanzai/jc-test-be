<?php
class Skookum_Cache_Apc {

    protected static $_instance;

    /**
     * Make a true factory pattern.
     *
     * @access  private
     */
    protected function __construct()
    {
        
    }

    /**
     * Loads a factory instance of the APC cache.
     *
     * @access  public
     * return   Zend_Cache
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            $frontendOptions = array(
                'automatic_serialization' => true,
                'lifetime' => in_array(APPLICATION_ENV, array('local', 'development')) ? 300 : 3600
            );
            try {
                self::$_instance = Zend_Cache::factory('Core', 'Apc', $frontendOptions);
            } catch (Exception $e) {
                $backendOptions  = array(
                    'cache_dir' => APPLICATION_PATH . '/data/cache/apc',
                    'automatic_serialization' => true
                );

                // ensure the cache directory exists
                if (!is_dir($backendOptions['cache_dir'])) {
                    @mkdir($backendOptions['cache_dir'], 600, true);
                }

                self::$_instance = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
            }
            
        }
        
        return self::$_instance;
    }

}