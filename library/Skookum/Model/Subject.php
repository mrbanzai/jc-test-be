<?php
class Skookum_Model_Subject 
    extends Skookum_Model 
    implements SplSubject {

    /**
     * The observer action.
     */
    protected $_action;

    /**
     * Store an array of observers.
     *
     * @access  private
     */
    private $_observers = array();
    
    /**
     * Default constructor.
     *
     * @access  public
     */
    public function __construct()
    {
        $this->_observers = array();
    }

    /**
     * Attach an observer.
     *
     * @access  public
     */
    public function attach(SplObserver $observer)
    {
        $this->_observers[] = $observer;
    }

    /**
     * Detach an observer.
     *
     * @access  public
     */
    public function detach(SplObserver $observer)
    {
        // not implemented
    }

    /**
     * Notify attached observers.
     *
     * @access  public
     */
    public function notify()
    {
        foreach ($this->_observers as $obj) {
            $obj->update($this);
        }
    }

    /**
     * Sets the method to be called by the observers.
     *
     * @access  public
     * @param   string
     * @return  Skookum_Model_Subject
     */
    public function setMessage($action = '')
    {
        $this->_action = $action;
        return $this;
    }
    
    /**
     * Gets the method to be called by the observers.
     *
     * @access  public
     * @return  string
     */
    public function getMessage()
    {
        return $this->_action;
    }
    
}