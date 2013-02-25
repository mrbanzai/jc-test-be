<?php
class Image_Resize
{
	protected static $_types = array(
		'amazon' => 'Image_Resize_Amazon',
		'local' => 'Image_Resize'
	);
	
	protected $_opts = null;
	
	public static function factory($type, $opts = null)
	{
		if (!array_key_exists($type, self::$_types)) {
			throw new Exception('Invalid resize type: ' . $type);
		}
		$class = self::$_types[$type];
		return new $class($opts);
	}
	
	public function __construct($opts = null)
	{
		if ($opts) $this->setOptions($opts);
	}
	
	public function setOptions($opts)
	{
		$this->_opts = $opts;
		return $this;
	}
	
	public function getOptions()
	{
		return $this->_opts;
	}
	
	public function resize($basedir, $hash, array $files)
	{
        if (strlen($hash) < 8)
        {
            throw new Exception("Invalid hash. The hash must be larger than 8 characters. use sha1");
        }
		
		// grab the absolute filepath to the upload directory
		$upload_dir = rtrim($basedir, "/");
        @mkdir($upload_dir, 0755, true);
		
        if (!is_dir($upload_dir))
        {
            throw new Exception("Unable to make upload directory '$upload_dir'");
        }
		
        $uploaded = array();

        // lopp through all of the files that have been uploaded
        foreach($files as $filepath => $resizeopts)
        {
            $fileext = pathinfo($filepath, PATHINFO_EXTENSION);
            
            // loop through the resize options for the current file
            foreach($resizeopts as $name => $opts)
            {
                $savepath = $upload_dir . '/' . $hash . '_' . $name . '.' . $fileext;
                $thumb = PhpThumb_Factory::create($filepath, $opts);
                
				// adaptive resize based on supplied dimensions
				$thumb->adaptiveResize($opts['dimensions']['height'], $opts['dimensions']['width']);
                
                if ($thumb->save($savepath, $fileext))
                {
                    if (empty($uploaded[$filepath])) $uploaded[$filepath] = array();
                    
                    $uploaded[$filepath][$name] = array(
						'path' => $savepath,
						'extension' => $fileext
					);
                }
            }
			
			@unlink($filepath);
        }

		if (count($uploaded) != count($files))
		{
			throw new Exception('An error occurred during upload and not all sizes could be saved.');
		}

        return $uploaded;
	}
}