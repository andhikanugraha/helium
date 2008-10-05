<?php

// Helium framework
// class Helium_HTTPResponse
// for handling response headers, etc.

class Helium_HTTPResponse {
	public $response_code = 200;
	public $content_type = 'text/html';

	private $sent = false;

	public static $response_codes = array(200 => 'OK',
										  301 => 'Moved Permanently',
										  302 => 'Found',
										  401 => 'Unauthorized',
										  403 => 'Forbidden',
										  404 => 'Not Found',
										  405 => 'Method Not Allowed',
										  500 => 'Internal Server Error');

	private $mime_shortcodes = array('text' => 'text/plain',
									'html' => 'text/html',
									'xml' => 'text/xml',
									'css' => 'text/css',
									'js' => 'application/x-javascript',
									'json' => 'application/x-javascript');
	private $modified_content_type = false;

	private $http_version = '1.1';

	public function __construct() {
		$this->check_headers_sent();
	}

	private function check_headers_sent() {
		if (headers_sent()) {
			$this->sent = true;
			return true;
		}

		global $conf;
		$this->http_version = $conf->http_version;
	}

	private function send_header($string, $replace = true) {
		if ($this->check_headers_sent())
			return false;

		header($string, $replace);

		return true;
	}

	public function set_response_code($code) {
		if ($this->check_headers_sent())
			return false;

		$code = intval($code);
		if (!self::$response_codes[$code])
			return;

		$message = self::$response_codes[$code];
		$string = 'HTTP/' . $this->http_version . " $code $message";

		$this->send_header($string, true);

		return true;
	}

	public function redirect($uri, $code = 302) {
		$parsed_uri = parse_url($uri);

		// relative URI
		if (!$parsed_uri['scheme']) {
			global $conf;
			$uri = ltrim($uri, '/');
			$uri = $conf->base_url . $uri;
		}

		$string = 'Location: ' . $uri;
		if (!$this->set_response_code($code) || !$this->send_header($string))
			throw new Helium_Exception(Helium_Exception::failed_to_redirect, $uri);
	}

	public function set_content_type($content_type) {
		if (strpos($content_type, '/') === false)
			$content_type = $this->mime_shortcodes[$content_type];
		if (!$content_type && $this->modified_content_type)
			$content_type = 'text/plain';

		$string = 'Content-type: ' . $content_type;
		$try = $this->send_header($string);
		if ($try)
			$this->modified_content_type = true;

		return $try;
	}

	// a string expire_in can be defined in seconds, days, weeks, months (a Julian year divided by 12), or years (a Julian year, 365.25 days)

	public function set_cookie($name, $value, $expire_in = 0, $secure = null, $httponly = false) {
		if ($this->check_headers_sent())
			return false;

		if (is_string($expire_in)) {
			$expire_in = String::to_seconds($expire_in);
			if ($expire_in === false)
				return false;
		}

		global $conf;

		$path = parse_url($conf->base_url, PHP_URL_PATH);
		$domain = $conf->host;
		$expire = 0;
		if ($expire_in)
			$expire = time() + $expire_in;
		if ($secure === null)
			$secure = ($conf->scheme == 'https');

		setcookie($name, $value, $expire, $secure, $httponly);
	}
}