<?php

class Forms_FormBase extends Zend_Form
{
    protected $_standardElementDecorator = array(
        'ViewHelper',
        array('LabelError', array('escape'=>false)),
        array('HtmlTag', array('tag'=>'li'))
    );

	protected $_labelOnlyElementDecorator = array(
        array('LabelError', array('escape'=>false)),
        array('HtmlTag', array('tag'=>'li'))
    );

	protected $_noLabelElementDecorator = array(
        'ViewHelper',
		array('HtmlTag', array('tag'=>'li'))
    );

    protected $_radioElementDecorator = array(
        'ViewHelper',
        array('LabelError', array('escape'=>false)),
        array('HtmlTag', array('tag'=>'li', 'class'=>'radiobuttons'))
    );

    protected $_fileElementDecorator = array(
        'File',
        array('LabelError', array('escape'=>false)),
        array('HtmlTag', array('tag'=>'li', 'class'=>'fileselector'))

    );

	/**
	 * Creates a decorator for custom styled file input buttons. May require
	 * adding other decorators for placement of the button.  Generally, this
	 * will sit inside of an image on the bottom right or left.
	 *
	 * Requires a parent element to wrap the image and be an exact size as well
	 * as have relative positioning.
	 */
	protected $_customFileElementDecorator = array(
		'File',
		array('HtmlTag', array('tag'=>'span', 'class'=>'button btn-upload bottom-right hidden', 'text' => 'edit'))
	);

	protected $_hiddenElementDecorator = array(
        array('ViewHelper'),
        array('HtmlTag', array('tag'=>'li', 'class'=>'hidden')),
		array('LabelError', array('escape'=>false))
    );

    protected $_nakedElementDecorator = array(
        'ViewHelper'
    );

	protected $_nakedLabeledElementDecorator = array(
        'ViewHelper',
		array('LabelError', array('escape'=>false)),
		array('HtmlTag', array('tag'=>'span', 'class'=>'mini_login_field'))
    );

    protected $_standardGroupDecorator = array(
        'FormElements',
        array('HtmlTag', array('tag'=>'ul'))
    );


	protected $_nakedGroupDecorator = array(
        'FormElements'
    );

    protected $_buttonGroupDecorator = array(
        'FormElements',
        array('HtmlTag', array('tag'=>'div', 'class'=>'buttons_group'))
    );

	public $options;

    public function __construct($options = null)
    {
        // Path setting for custom decorations MUST ALWAYS be first!
        $this->addElementPrefixPath('Forms_Form_Decorator', 'Forms/Form/Decorator/', 'decorator');
        $this->_setupTranslation();
        $this->options = $options;

        parent::__construct($options);
        $this->setAttrib('accept-charset', 'UTF-8');

		// create a dynamic id for the form if one isn't set
		if (!$this->getAttrib('id'))
		{
			$classname = get_class($this);
			$id = substr(get_class($this), strrpos($classname, '_') + 1) . '_' . time();
			$this->setAttrib('id', $id);
		}

        $this->setDecorators(array(
            'FormElements',
            'Form'
        ));

		//$this->addPrefixPath('Forms_Form', 'Forms/Form');
		//$this->addPrefixPath('Forms_Form_Element', 'Forms/Form/Element/');

		//$this->setElementFilters(array('StringTrim'));
    }

	public function init()
	{
		$this->addPrefixPath('Forms_Form', 'Forms/Form');
	}

    protected function _setupTranslation()
    {
        if (self::getDefaultTranslator()) {
            return;
        }
        $path = dirname(dirname(dirname(__FILE__)))
            . '/translate/forms.php';
        $translate = new Zend_Translate('array', $path, 'en');
        Zend_Form::setDefaultTranslator($translate);
    }

}