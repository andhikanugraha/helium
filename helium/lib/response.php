<?php

// Helium framework
// class HeliumHTTPResponse
// for handling response headers, etc.

class HeliumHTTPResponse {
	const http_version = '1.1';

	public $response_code = 200;
	public $content_type = 'text/html';
	
	private $sent = false;

	public static $response_codes = array(401 => 'Unauthorized',
					  403 => 'Forbidden',
					  404 => 'Not Found',
					  405 => 'Method Not Allowed',
					  500 => 'Internal Server Error');

	public function __construct() {
		$this->check_headers_sent();
	}
	
	private function check_headers_sent() {
		if (headers_sent()) {
			$this->sent = true;
			return true;
		}
	}
	
	private function send_header($string, $replace = true) {
		if ($this->check_headers_sent())
			return false;

		if (@header($string))
			return true;
		else
			return false;
	}
	
	public function send_response_code($code) {
		$code = intval($code);
		if (!$this->response_codes[$code])
			return;

		$message = self::response_codes($code);
		$string = 'HTTP/' . self::http_version . " $code $message";
		if (@header($string, true, $code))
			return true;
		else
			return false;
	}

	public function redirect($uri) {
		$string = 'Location: ' . $uri;
		return $this->send_header($string);
	}
	
	public function set_content_type($content_type) {
		$string = 'Content-type: ' . $content_type;
		return $this->send_header($string);
	}
}