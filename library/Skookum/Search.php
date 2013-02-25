<?php
class Skookum_Search extends Auth_Controller_Action
{
    /**
     * Loads the elastica client.
     */
    protected $_elasticaClient;
    protected $_elasticaIndexes = array();
    protected $_elasticaTypes = array();
    
    /**
     * The default threshold.
     */
    const THRESHOLD = 0.4;

    /**
     * Search for a given query in a particular index/type.
     *
     * @param   string  $index
     * @param   string  $type
     * @param   array   $query
     * @param   float   $threshold  The results should all score above this threshold
     * @param   array   $whitelist  Specify the return data
     */
    protected function elasticaQuery($index, $type, $query, $threshold = self::THRESHOLD, $whitelist = array())
    {
        // ensure the type is loaded
        $this->getElasticaType($index, $type);

        // create the query object
        $query = new Elastica_Query($query);

        // perform the query
        $matches = array();
        $results = $this->_elasticaTypes[$index][$type]->search($query);
        if ($results->getTotalHits() > 0) {
            foreach ($results as $r) {
                if ($r->getScore() > $threshold) {
                    if (!empty($whitelist)) {
                        $data = $r->getData();
                        foreach ($data as $k => $v) {
                            if (!in_array($k, $whitelist)) {
                                unset($data[$k]);
                            }
                        }
                        $matches[] = $data;
                    } else {
                        $matches[] = $r->getData();
                    }
                }
            }
        }

        // return valid matches
        return $matches;
    }

    /**
     * Returns the Elastica type, generated on the fly if necessary.
     *
     * @access  protected
     * @return  Elastica_Type
     */
    protected function getElasticaType($index, $type)
    {
        if (!isset($this->_elasticaTypes[$index][$type]))  {
            $this->_elasticaTypes[$index][$type] = $this->getElasticaIndex($index)->getType($type);
        }
        return $this->_elasticaTypes[$index][$type];
    }

    /**
     * Returns an index.
     *
     * @access  public
     * @return  Elastica_Index
     */
    protected function getElasticaIndex($index)
    {
        if (!isset($this->_elasticaIndexes[$index])) {
            $this->_elasticaIndexes[$index] = $this->getElasticaClient()->getIndex($index);
            $this->_elasticaTypes[$index] = array();
        }
        return $this->_elasticaIndexes[$index];
    }

    /**
     * Lazy load the elastica client.
     *
     * @access  protected
     * @return  Elastica_Client
     */
    protected function getElasticaClient()
    {
        // lazy loading
        if (!$this->_elasticaClient) {
           $this->_elasticaClient = new Elastica_Client();
        }
        return $this->_elasticaClient;
    }

}