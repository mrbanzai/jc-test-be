<?php
/**
 * Writing custom validators:
 * http://framework.zend.com/manual/en/zend.validate.writing_validators.html
 */
class Forms_Form_Validate_Usertoken extends Zend_Validate_Abstract
{
    const MSG_USAGE     = 'msgUsage';
    const MSG_INVALID   = 'msgInvalid';
    const MSG_EXPIRED   = 'msgExpired';
    const MSG_REQUIRED  = 'msgRequired';

    protected $_messageTemplates = array(
        self::MSG_USAGE     => "The maximum usage for the invitation code provided has been reached.",
        self::MSG_INVALID   => "The invitation code you entered does not appear to be valid.",
        self::MSG_EXPIRED   => "The invitation code you entered has expired.",
        self::MSG_REQUIRED  => "An invitation code is required to register."
    );

    /**
     * Ensure a token is valid.
     *
     * @access  public
     * @param   string  $token
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // ensure field is set
        if (empty($value)) {
            $this->_error(self::MSG_REQUIRED);
            return false;
        }

        // check for a database match
        $UserToken = new UserToken();
        $result = $UserToken->validate($value);

        if ($result === TRUE) return TRUE;

        switch ($result) {
            case 'invalid':
                $this->_error(self::MSG_INVALID);
                return FALSE;
            case 'expired':
                $this->_error(self::MSG_EXPIRED);
                return FALSE;
            case 'usage':
                $this->_error(self::MSG_USAGE);
                return FALSE;
            default:
                return TRUE;
        }
    }
}