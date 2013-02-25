<?php
abstract class Skookum_Model_Observer
    extends Skookum_Model
    implements SplObserver {
    
    /**
     * base update handler. compiles a method name based on the subjects message.
     * The method is compiled from the subjects class name and an action supplied in the message.
     * 
     * @access  public
     * @param   SplSubject  $subject
     */
    public function update(SplSubject $subject)
    {
        $message = $subject->getMessage();

        $methodPrefix = str_replace("_", "", get_class($subject));
        
        // ensure an action has been supplied
        if (empty($message['action'])) {
            throw new Exception('An action must be suppied in the subjects message.');
        }
        
        $action = str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $message['action']))));
        $method = '_on' . $methodPrefix . $action;
        if (method_exists($this, $method)) {
            $this->{$method}($message);
        }
    }
    
    /**
     * handles observer callbacks that have not been implemented
     * 
     * @access public
     * @param string $name
     * @param array $args
     * @throws Exception
     */
    public function __call($name, $args)
    {
        throw new Exception("method '$name' does not exist!");
    }
}
