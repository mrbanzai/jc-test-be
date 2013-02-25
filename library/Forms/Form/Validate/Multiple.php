<?php
/**
 * Writing custom validators:
 * http://framework.zend.com/manual/en/zend.validate.writing_validators.html
 */
class Forms_Form_Validate_Multiple extends Zend_Validate_Abstract
{
    const INVALID_MULTIPLE = 'invalidMultiple';

    public $field = '';
    public $subfield = '';
    public $minimum = 0;
    public $maximum = 10;

    protected $_messageTemplates = array(
        self::INVALID_MULTIPLE   => "You must complete between %min% and %max% entries.",
    );

    protected $_messageVariables = array(
        'min' => 'minimum',
        'max' => 'maximum'
    );

    public function __construct($options, $title = null) {
        if (!empty($options['min'])) $this->minimum = (int) $options['min'];
        elseif (!empty($options['minimum'])) $this->minimum = (int) $options['minimum'];

        if (!empty($options['max'])) $this->maximum = (int) $options['max'];
        elseif (!empty($options['maximum'])) $this->maximum = (int) $options['maximum'];

        if (!empty($options['field'])) $this->field = $options['field'];
        if (!empty($options['subfield'])) $this->subfield = $options['subfield'];
    }

    public function isValid($value)
    {
        $this->_setValue($value);

        // check for min to max fields
        if (empty($_POST[$this->field])) {
            $this->_error(self::INVALID_MULTIPLE);
            return false;
        }

        $count = count($_POST[$this->field]);
        if ($count < $this->minimum || $count > $this->maximum) {
            $this->_error(self::INVALID_MULTIPLE);
            return false;
        } else {
            $count = 0;
            foreach ($_POST[$this->field] as $val) {
                if (!empty($subfield) && !empty($val[$subfield])) {
                    ++$count;
                } else if (empty($subfield) && !empty($val)) {
                    ++$count;
                }
            }

            if ($count < $this->minimum || $count > $this->maximum) {
                $this->_error(self::INVALID_MULTIPLE);
                return false;
            }
        }
        return true;
    }
}