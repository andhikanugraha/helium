<?php

// Helium framework
// class Helium_Session
// global $session;

class Helium_Session implements Iterator {
	const session_end = 0;

	private $record;
	private $store = array();
	private $name = '';
	private $cookie_lifetime;
	private $cookie_name = '';

	public function __construct() {
		global $conf;

		$this->cookie_lifetime = $conf->session_lifetime;
		$this->cookie_name = sha1($conf->base_url) . '_session';
	}

	// overloading

	public function __set($name, $value) {
		$this->init();
		$this->store[$name] = $value;
		$this->save_store();
	}

	public function __get($name) {
		$this->init();
		return $this->store[$name];
	}

	public function __isset($name) {
		return isset($this->store[$name]);
	}

	public function __unset($name) {
		unset($this->store[$name]);
		$this->save_store();
	}

	// iterating

	public function rewind() {
		$this->init();
		reset($this->store);
	}

	public function current() {
		$this->init();
		$var = current($this->store);
		return $var;
	}

	public function key() {
		$this->init();
		$var = key($this->store);
		return $var;
	}

	public function next() {
		$this->init();
		$var = next($this->store);
		return $var;
	}

	public function valid() {
		$this->init();
		$var = $this->current() !== false;
		return $var;
	}

	// destroy the current session
	public function destroy() {
		if (!$this->record)
			return;

		$this->record->destroy();
		$this->record = null;
	}

	public function name() {
		$this->init();
		return $this->name;
	}

	private function init() {
		global $conf;
		static $done;

		if ($done)
			return;
		$done = true;

		$this->name = $this->get_session_name();

		if ($this->name) {
					$this->record = Session::find_by_name($this->name);
									if (!$this->validate()) {
								$this->destroy();
			}
		}
		if (!$this->record) {
			$record = new Session;
			$this->name = $record->name = $this->generate_session_name();
			$record->expire = time() + String::to_seconds($conf->session_lifetime);
			$record->remote_addr = $_SERVER['REMOTE_ADDR'];
			$this->record = $record;
			$this->record->save();
		}

		$this->store = $this->record->store;
		$this->refresh_session();		
		$this->set_session_cookie(); // update cookie
	}

	private function save_store() {
		$this->record->store = $this->store;
		$this->record->save();
	}

	private function refresh_session() {
		global $conf;
		$this->record->expire = time() + String::to_seconds($conf->session_lifetime);
		$this->record->save();
	}

	public function set_to_expire_in($string) {
		if (($seconds = String::to_seconds($string)) === false)
			return false;

		$this->cookie_lifetime = $string;

		if ($seconds > 0)
			return $this->set_session_cookie();

		$this->record->expire = time() + $seconds;
		return $this->record->save();
	}

	public function set_temporary_cookie() {
		global $response;
		$this->cookie_lifetime = 0;
		$this->set_session_cookie();
		$response->set_cookie($this->cookie_name . '_die', true);
	}

	private function get_session_name() {
		global $conf;

		$name = $_COOKIE[$this->cookie_name];
		$match = preg_match("/^[a-zA-Z0-9]{40}$/", $name);
		if (!$match)
			return false;

		return $name;
	}

	private function set_session_cookie() {
		global $conf, $response;

		if ($_COOKIE[$this->cookie_name . '_die'])
			$response->set_cookie($this->cookie_name, $this->name);
		else
			$response->set_cookie($this->cookie_name, $this->name, $this->cookie_lifetime);
	}

	private function validate() {
		// step 0: check syntax of cookie
		// handled by get_session_name()

		// step 1: check expiration date
				if ($this->record->expire <= time())
			return false;

		if (!$conf->strict_sessions)
			return true;

		// step 2: check IP
		$remote_addr = $_SERVER['REMOTE_ADDR'];
		if ($remote_addr != $this->record->remote_addr)
			return false;

		// step 3: check port
		$remote_port = $_SERVER['REMOTE_PORT'];
		if ($remote_port != $this->record->remote_port)
			return false;
	}

	private function generate_session_name() {
		return String::random_hash();
	}
}

class Session extends Helium_ActiveRecord {
	public $name = '';
	public $store = array();
	public $remote_addr = '';
	public $remote_port = 0;
	public $expire = 0;

	public static function find_by_name($name) {
		return find_first_record('session', array('name' => $name));
	}
}