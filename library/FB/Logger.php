<?php
require_once BASE_PATH . '/library/FB/FirePHP.class.php';

Class FB_Logger extends FirePHP {

	public function info($message, $label = FALSE) {
		if (!in_array(APPLICATION_ENV, array('staging', 'production')))
			self::process($message, $label, FirePHP::INFO);
	}

	public function warn($message, $label = FALSE) {
		if (!in_array(APPLICATION_ENV, array('staging', 'production')))
			self::process($message, $label, FirePHP::WARN);
	}

	public function error($message, $label = FALSE) {
		if (!in_array(APPLICATION_ENV, array('staging', 'production')))
			self::process($message, $label, FirePHP::ERROR);
	}

/*
	public function dump($Key, $Variable) {
		if (!in_array(APPLICATION_ENV, array('staging', 'production')))
			return self::process($Variable, $Key, FirePHP::DUMP);
	}
*/

	private function process($message, $label, $type) {
		if ($label) {
			try {
				parent::fb($message, $label, $type);
			} catch (Exception $e) {
				self::error_handler($e);
			}
		} else {
			try {
				parent::fb($message, $type);
			} catch (Exception $e) {
				self::error_handler($e);
			}
		}
	}

	private function error_handler($e) {
		self::error($e);
	}

}