<?php

class Messager
{
	/**
	 * Queue up messages for display.
	 */
	public static function add($type, $message, $message_id = null) {
		if (is_array($message)) {
			foreach ($message as $m)
				$_SESSION['messages'][$type][] = $m;
		} else if (!is_null($message_id)) {
			$_SESSION['messages'][$type][$message_id] = $message;
		} else {
			$_SESSION['messages'][$type][] = $message;
		}
		
		// filter out duplicate messages
		$_SESSION['messages'][$type] = array_unique($_SESSION['messages'][$type]);
	}

	/**
	 * Clear the message queue.
	 */
	public static function clear() {
		$_SESSION['messages'] = array();
	}

	/**
	 * Display messages if applicable.
	 */
	public static function display() {

		$view = Zend_Layout::getMvcInstance()->getView();

		$output = '';

		// Grab any messages in session and put them in proper placeholders
		if (!empty($_SESSION['messages']['error'])) {
			$errors = $view->placeholder('error');
			foreach($_SESSION['messages']['error'] as $m) {
				$view->placeholder('error')->append('<li>' . $m . '</li>');
			}
			unset($_SESSION['messages']['error']);
			$output .= $errors;
		}

		if (!empty($_SESSION['messages']['success'])) {
			$successes = $view->placeholder('success');
			foreach($_SESSION['messages']['success'] as $m) {
				$view->placeholder('success')->append('<li>' . $m . '</li>');
			}
			unset($_SESSION['messages']['success']);
			$output .= $successes;
		}

		if (!empty($_SESSION['messages']['message'])) {
			$messages = $view->placeholder('message');
			foreach($_SESSION['messages']['message'] as $m) {
				$messages->append('<li>' . $m . '</li>');
			}
			unset($_SESSION['messages']['message']);
			$output .= $messages;
		}

		if ($output != '')
			$output = '<div id="message_box">'.$output.'</div>';

		return $output;
	}

	/**
	 * Initialize placeholders
	 *
	 */
	public static function setupPlaceholders()
    {
        // PLACEHOLDER FOR SYSTEM MESSAGES
        $view = Zend_Layout::getMvcInstance()->getView();

		$message = $view->placeholder('error');
        $message->setPrefix('<ul class="notice error">')
                ->setPostfix('</ul>');

		$message = $view->placeholder('success');
        $message->setPrefix('<ul class="notice success">')
                ->setPostfix('</ul>');

		$message = $view->placeholder('message');
        $message->setPrefix('<ul class="notice message">')
                ->setPostfix('</ul>');
    }

}
