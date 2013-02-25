<?php
class Skookum_View_Helper_CsrfToken extends Zend_View_Helper_Abstract
{
    public function csrfToken()
    {
        return Skookum_Security_Csrf::getToken();
    }
}