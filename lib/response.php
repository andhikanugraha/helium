<?php

// Helium framework
// class Helium_HTTPResponse
// for handling response headers, etc.

class Helium_HTTPResponse {
	public $response_code = 200;
	public $content_type = 'text/html';
	
	private $sent = false;

	public static $response_codes = array(401 => 'Unauthorized',
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

		if (@header($string))
			return true;
		else
			return false;
	}
	
	public function set_response_code($code) {
		$code = intval($code);
		if (!$this->response_codes[$code])
			return;

		$message = self::$response_codes[$code];
		$string = 'HTTP/' . self::http_version . " $code $message";
		if (@header($string, true, $code))
			return true;
		else
			return false;
	}

	public function redirect($uri, $code = 302) {
		$parsed_uri = parse_url($uri);

		// relative URI
		if (!$parsed_uri['scheme']) {
			global $conf;
			$uri = $conf->base_url . $uri;
		}

		$string = 'Location: ' . $uri;
		if (!$this->set_response_code($code) || !$this->send_header($string))
			throw new Helium_Exception(Helium_Exception::failed_to_redirect);
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
}