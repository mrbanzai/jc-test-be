<?php

class Forms_SubformBase extends Zend_Form_SubForm
{
    protected $_nakedElementDecorator = array(
        'ViewHelper'
    );

    protected $_standardElementDecorator = array(
        'ViewHelper',
        array('LabelError', array('escape'=>false)),
        array('HtmlTag', array('tag'=>'li'))
    );

	protected $_hiddenElementDecorator = array(
        array('ViewHelper'),
        array('HtmlTag', array('tag'=>'li', 'class'=>'hidden'))
    );

    protected $_buttonElementDecorator = array(
        'ViewHelper'
    );

    protected $_buttonGroupDecorator = array(
        'FormElements',
		array('HtmlTag', array('tag'=>'li'))
    );

    public function __construct($options = null)
    {
        // Path setting for custom decorations MUST ALWAYS be first!
        $this->addElementPrefixPath('Forms_Form_Decorator', 'Forms/Form/Decorator/', 'decorator');
        $this->_setupTranslation();

        parent::__construct($options);

        $this->setAttrib('accept-charset', 'UTF-8');
        $this->setDecorators(array(
            'FormElements',
			array('HtmlTag', array('tag' => 'ul')),
			'Fieldset'
        ));
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