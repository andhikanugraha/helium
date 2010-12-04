<?php

require_once 'sessions/session.php';

class HeliumSessions {
	const cookie_lifetime = 1209600;
	const any = 'any';
	const all = 'all';

	public $user;
	public $stat_record;
	public $logged_in;

	private $session;
	private $session_id;
	private $cookie_name; // this cookie is renewed for two weeks at every visit.
	private $login_cookie_name; // login cookie, expires when browser closes.
	private $remember_cookie_name; // second auxiliary cookie

	public function __construct() {
		if (!$this->cookie_name)
			$this->cookie_name = Helium::$app_id;

		if (!$this->login_cookie_name)
			$this->login_cookie_name = $this->cookie_name . 'l';

		if (!$this->remember_cookie_name)
			$this->remember_cookie_name = $this->cookie_name . 'r';

		$session_id = intval($_COOKIE[$this->cookie_name]);

		if (!$session_id || !$this->verify_session($session_id))
			$this->start_session();
		
		$this->logged_in = (bool) $this->session->user;
		$this->user = &$this->session->user;
		$this->data = &$this->session->data;

		if ($this->session->persistent)
			$this->prolong_cookie();

		register_shutdown_function(array($this, 'save_session'));
	}

	public function renew($seconds = null) {
		if (!$seconds)
			$seconds = self::cookie_lifetime;

		$this->extend_cookie_lifetime($seconds);
	}

	public function save_session() {
		if ($this->session instanceof Session) {
			$this->session->save();
		}
		else
			return false;
	}

	private function verify_session($id) {
		// session has to match three things: session ID, IP address and user agent
		$session = Session::find(array('id' => $id,
									   'ip_address' => $_SERVER['REMOTE_ADDR'],
									   'user_agent' => $_SERVER['HTTP_USER_AGENT']));
		$session = $session[0];

		if (!$session) {
			$this->dump_session();
			return false;
		}

		$this->session = $session;
		$this->session_id = $id;
		$this->send_cookie();

		return true;
	}

	private function start_session() {
		$session = new Session;
		$session->ip_address = $_SERVER['REMOTE_ADDR'];
		$session->user_agent = $_SERVER['HTTP_USER_AGENT'];
		// $session->session_key = $this->generate_session_key();
		$session->save();
		$this->session = $session;
		$this->session_id = $session->id;
		$this->send_cookie();
	}

	private function restart_session() {
		$this->dump_session();
		$this->start_session();
	}

	private function dump_session() {
		if ($this->session)
			$this->session->destroy();
	}

	private function send_cookie() {
		if (!headers_sent())
			setcookie($this->cookie_name, $this->session_id, 0, '/');
	}

	private function prolong_cookie() {
		if (!headers_sent())
			setcookie($this->cookie_name, $this->session_id, time() + self::cookie_lifetime, '/');
	}

	public function make_persistent() {
		$this->session->persistent = true;
		$this->session->save();
		$this->prolong_cookie();
	}
	
	public function make_temporary() {
		$this->session->persistent = false;
		$this->session->save();
		$this->send_cookie();
	}

	public function unlink_user() { // a.k.a. low-level logout
		$this->session->user_id = '';
		$this->session->persistent = false;
		$this->session->save();
		$this->user = null;
		$this->logged_in = false;
	}
	
	public function link_user($user) { // a.k.a. low-level login
		if (!$this->session->link($user));
			return false;

		$this->session->save();
		$this->user = $this->session->user;
		$this->logged_in = true;
	}
}