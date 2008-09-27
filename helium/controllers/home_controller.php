<?php

class HomeController extends HeliumController {
	public $content_type = 'text/plain';
	protected $__smarty = false;

	public function index() {
		global $response;

		$response->set_content_type('text/plain');
		
		echo 'w00t!';
	}
}