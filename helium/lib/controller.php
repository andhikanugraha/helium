<?php

abstract class HeliumController {
	private $__forbidden_words = array('__forbidden_words', 'smarty');
	private $__action = '';
	private $__params;

	// use this as constructor for descendant classes
	public function __build() {}

	public function __construct() {
		global $conf;
		$this->__action = $conf->default_action;
		$this->__build();
	}

	public function __set_action($action = '', $strict = false) {
		if ($action)
			$this->__action = $action;

		return $this;
	}
	
	public function __set_params($params = array()) {
		$this->__params = $params;
		return $this;
	}
	
	public function __do_action() {
		if (!method_exists($this, $this->__action))
			throw new HeliumException(HeliumException::no_action);

		return $this->{$this->__action}();
	}

	public function __output() {
		global $response;
		$response->set_response_code($this->response_code);
		$response->set_content_type($this->content_type);
		if (!$this->smarty)
			return;
	}
	
	public function __yell($action = '', $params = array()) {
		$this->__set_action($action);
		$this->__do_action();
		$this->__output();
	}

	// for default configuration
	public function index() {}
}
