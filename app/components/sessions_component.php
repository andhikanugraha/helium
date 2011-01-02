<?php

// Helium
// Default, basic sessions component
// defines a $session property in the active controller that utilizes PHP built-in session handling.
// $controller->session is a reference to $_SESSION.

class SessionsComponent extends HeliumComponent {
	public $session = array();
	
	public function init($controller) {
		$controller->session = new HeliumBasicSessionHandler;
		if (!$controller->session_name)
			$controller->session_name = 'helium';

		session_start($controller->session_name);

		$this->session = &$_SESSION;
	}

	public function __destruct() {
		session_write_close();
	}

}