<?php
class Forms_Form_Decorator_FileImage extends Zend_Form_Decorator_File
{
    public function render($content)
    {
        $imgsrc = $this->getOption('imgsrc');
        
        $this->setOption('placement', self::PREPEND);
        
        if ($imgsrc)
        {
            $content = '<img src="' . $imgsrc . '" alt=""/>';
        }
        
        return parent::render($content);
    }
}