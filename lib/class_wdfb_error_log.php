<?php
class Wdfb_ErrorLog {
	var $_limit = 20;

	function get_all_errors () {
		$errors = get_option('wdfb_error_log');
		return $errors ? $errors : array();
	}

	function purge_errors () {
		update_option('wdfb_error_log', array());
	}

	function error ($function, $exception) {
		$this->_update_queue(array(
			'date' => time(),
			'area' => $function,
			'user_id' => get_current_user_id(),
			'type' => 'exception',
			'info' => $exception
		));
	}

	function _update_queue ($error) {
		$errors = $this->get_all_errors();
		if (count($errors) >= $this->_limit) $errors = array_slice($errors, (($this->_limit * -1)-1), $this->_limit-1);
		$errors[] = $error;
		update_option('wdfb_error_log', $errors);
	}
}