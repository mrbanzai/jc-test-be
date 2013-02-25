<?php
abstract class Image_Uploader
{
    protected $form;
    protected $filename;
	protected $fieldname;
    protected $basedir;
	protected $dimensions = array();
    protected $resizer;

	/**
	 * set the form object to get data from
	 *
	 * @access public
	 * @return Image_Uploader
	 */
    public function setForm(Forms_FormBase $form)
    {
        $this->form = $form;
        return $this;
    }

	/**
	 * get the form object
	 *
	 * @access public
	 * @return Image_Uploader
	 */
    public function getForm()
    {
        return $this->form;
    }

	/**
	 * set the filename to be use for saving the file (no extension)
	 *
	 * @access public
	 * @return Image_Uploader
	 */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

	/**
	 * get the filename use to save the file
	 *
	 * @access public
	 * @return Image_Uploader
	 */
    public function getFilename()
    {
        return $this->filename;
    }

	/**
	 * set dimensions for the saved file
	 *
	 * @access public
	 * @return Image_Uploader
	 */
	public function addDimensions($name, $height, $width)
	{
		$this->dimensions[$name] = array();
		$this->dimensions[$name]['dimensions'] = array('height' => $height, 'width' => $width);
		return $this;
	}

	/**
	 * get the dimensions for the saved file
	 *
	 * @access public
	 * @return Image_Uploader
	 */
	public function getDimensions()
	{
		return $this->dimensions;
	}

	/**
	 * set the form field name
	 *
	 * @access public
	 * @return Image_Uploader
	 */
	public function setFieldname($fieldname)
	{
		$this->fieldname = $fieldname;
		return $this;
	}

	/**
	 * get the form field name
	 *
	 * @access public
	 * @return Image_Uploader
	 */
	public function getFieldname()
	{
		return $this->fieldname;
	}

	public function setHash($hash)
	{
		$this->hash = $hash;
		return $this;
	}

	public function getHash()
	{
		return $this->hash;
	}

	public function setBaseDir($dir)
	{
		$this->basedir = $dir;
		return $this;
	}

	public function getBaseDir()
	{
		return $this->basedir;
	}

	public function setResizer(Image_Resize $resizer)
	{
		$this->resizer = $resizer;
		return $this;
	}

	public function getResizer()
	{
		if (!$this->resizer) $this->resizer = new Image_Resize();
		return $this->resizer;
	}

	/**
	 * Upload the file.
	 *
	 * @access 	public
	 * @param	bool	$useZendForm
	 * @return 	Image_Uploader
	 */
    public function upload($useZendForm = true)
    {
		if ($useZendForm) {
			$values = $this->getForm()->getValues();
			$filefield = $this->getForm()->{$this->getFieldname()};
		} else if (!isset($_FILES[$this->getFieldname()])) {
			throw new Exception('You must set the name of the file upload field for the form.');
		} else {
			$filefield = $_FILES[$this->getFieldname()];
		}

		if (!$filefield) {
			throw new Exception('You must set the name of the file upload field for the form.');
		}

		if (!empty($values[$this->getFieldname()])) {
			$filepath = $filefield->getFilename();

			try {
				$images = $this->getResizer()->resize($this->getBaseDir(),
													  $this->getHash(),
													  $this->getUploadOptions($filepath));

				return $images;
			} catch (Exception $e) {
				error_log('Upload error: ' . $e->getMessage());
				return false;
			}
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