<?php

class HomeController extends HeliumController {
	protected function __build() {
		$this->use_smarty(false);
	}

	public function index() {
		global $response;
		
		echo 'w00t!';
	}
}