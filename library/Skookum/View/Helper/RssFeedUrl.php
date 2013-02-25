<?php

    class Skookum_View_Helper_RssFeedUrl extends Zend_View_Helper_Abstract {

        public function rssFeedUrl($type, $param) {
            switch ($type) {
                case 'category':
                    return $this->view->serverUrl() . '/rss/category/' . urlencode(strtolower($param));
                case 'state':
                    return $this->view->serverUrl() . '/rss/state/' . urlencode(strtolower($param));
                case 'recent':
                    return $this->view->serverUrl() . '/rss/recent';
            }
        }

    }