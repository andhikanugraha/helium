<?php

// Helium
// Default, basic sessions component

class SessionsComponent extends HeliumComponent {
	
	public function init($controller) {
		$controller->session = new Session;
	}
}

class Session {
	private $started = false;

	public function __get($name) {
		if (!$this->started)
			session_start();

		return $_SESSION[$name];
	}

	public function __set($name, $value) {
		if (!$this->started)
			session_start();

		$_SESSION[$name] = $value;
	}

	public function __destruct() {
		session_write_close();
	}
}