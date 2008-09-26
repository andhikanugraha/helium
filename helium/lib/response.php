<?php

// Helium framework
// class HeliumHTTPResponse
// for handling response headers, etc.

class HeliumHTTPResponse {
	public $status = 200;
	public $content_type = 'text/html';
	
	private $status_codes = array(200 => 'OK'
								  404 => 'Not Found');
}