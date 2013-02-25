<?php
class Forms_Form_UserAvatar extends Forms_FormBase
{

    public function init()
    {
		$this->clearDecorators();
        $this->setAction('/ajax/upload/type/user');

		// add the form element
		$element = new Zend_Form_Element_File('avatar');
	   	$element->setLabel('Avatar')
	   			->setDecorators($this->_customFileElementDecorator)
	   			->setAttrib('class', 'button upload')
	   			->setAttrib('allow', 'jpg,jpeg,png,gif')
	   			->addValidator('Extension', false, 'jpg,jpeg,png,gif');
	    $this->addElement($element, 'avatar');

		$this->addDisplayGroup(
			array('avatar'), 'entrydata',
			array(
				'disableLoadDefaultDecorators' => true,
				'decorators' => $this->_nakedGroupDecorator
			)
		);
	}

}