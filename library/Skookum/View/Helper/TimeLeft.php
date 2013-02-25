<?php
class Skookum_View_Helper_TimeLeft extends Zend_View_Helper_Abstract
{
    public $view;
    
    public function timeLeft($date, $endedlabel = null)
    {
        $curdate = time();
        $diff = $date - $curdate;
        
        if ($diff <= 0) {
            return ($endedlabel) ? ucwords(strtolower($endedlabel)) : 'Expired';
        }

        return $this->view->relativeTime($date, 2);
    }
    
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}