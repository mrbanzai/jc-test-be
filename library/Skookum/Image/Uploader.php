<?php
class Skookum_Image_Uploader extends Skookum_Uploader
{
    protected $_savedir;
	protected $_dimensions = array();
    protected $_resizer;
    protected $_hash;
	

	/**
	 * set dimensions for the saved file
	 *
	 * @access public
	 * @return Image_Uploader
	 */
	public function addDimensions($name, $height, $width)
	{
		$this->_dimensions[$name] = array();
		$this->_dimensions[$name]['dimensions'] = array('height' => $height, 'width' => $width);
		return $this;
	}
	
	/**
	 * get the dimensions for the saved file
	 *
	 * @access public
	 * @return array
	 */
	public function getDimensions()
	{
		return $this->_dimensions;
	}
	
    /**
     * set the upload hash
     *
     * @access public
     * @param string $hash
     * @return string
     */
	public function setHash($hash)
	{
		$this->_hash = $hash;
		return $this;
	}

    /**
     * get the upload hash
     *
     * @access public
     * @return string
     */	
	public function getHash()
	{
		return $this->_hash;
	}
	
    /**
     * set the base directory
     *
     * @access public
     * @param string $dir
     * @return string
     */
	public function setSaveDir($dir)
	{
		$this->_savedir = $dir;
		return $this;
	}
	
    /**
     * get the base directory
     *
     * @access public
     * @return string
     */
	public function getSaveDir()
	{
		return $this->_savedir;
	}

    /**
     * set the resize handler
     *
     * @access public
     * @param Skookum_Image_Resize $resizer
     * @return $this
     */	
	public function setResizer(Skookum_Image_Resize $resizer)
	{
		$this->_resizer = $resizer;
		return $this;
	}
	
    /**
     * get the resize handler. create a default if one is not given
     *
     * @access public
     * @return Skookum_Image_Resizer
     */
	public function getResizer()
	{
		if (!$this->_resizer) $this->_resizer = new Skookum_Image_Resize_Local();
		return $this->_resizer;
	}
	
	/**
	 * upload the file
	 *
	 * @access public
	 * @return Image_Uploader
	 */
    public function process()
    {
        $filefield = $this->getFile(); // get the file field

		if (!$filefield) {
			throw new Exception('You must set the name of the file upload field for the form.');
		}

        $tmpPath = $filefield->getFilename();

		if (!empty($tmpPath))
		{
			try
			{
				$images = $this->getResizer()
								->resize($this->getSaveDir(),
										 $this->getHash(),
										 $this->getUploadOptions($tmpPath));
				
				return $images;

			} catch (Exception $e) {
				throw new Exception($e->getMessage(), $e->getCode());
			}

		} else {
            // get the file's error and throw an exception
        }

		return false;
    }
	
	/**
	 * get options used to upload the file
	 *
	 * @access public
	 * @return Image_Uploader
	 */
    protected function getUploadOptions($filepath)
	{
        $settings = array($filepath => array());
        $dimensions = $this->getDimensions();
        
        if (empty($dimensions))
        {
            throw new Exception("No dimensions have been defined");
        }
        
        foreach((array) $dimensions as $name => $dimension)
        {
            $settings[$filepath][$name] = $dimension;
        }
        
        return $settings;
	}
}