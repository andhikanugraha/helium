<?php

abstract class HeliumController {
	protected $__params;
	protected $__action = '';

	private $__forbidden_words = array('__forbidden_words', 'smarty');
	private $__redirect;

	protected $use_smarty = true;
	protected $smarty_layout = '';

	// use this as constructor for descendant classes
	protected function __build() {}

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
		if ($params && !is_array($params))
			$params = array($params);

		$this->__params = $params;
		return $this;
	}
	
	public function __do_action() {
		if (!method_exists($this, $this->__action))
			throw new HeliumException(HeliumException::no_action);

		$try = $this->{$this->__action}();
		
		if ($this->__redirect)
			return $this->__execute($this->__redirect);

		return $this;
	}

	public function __output() {
		global $response;
		$response->set_response_code($this->response_code);
		$response->set_content_type($this->content_type);

		if (!$this->use_smarty)
			return;

		global $conf;
		if (!$conf->use_smarty)
			return;

		global $smarty, $router;
		$smarty = new SmartyOnHelium;
		$smarty->set_body($router->view);
		if ($this->smarty_layout)
			$smarty->set_layout($this->smarty_layout);

		$smarty->yell();
	}

	public function __yell($action = '', $params = array()) {
		$this->__set_action($action);
		$this->__do_action()->__output();
	}
	
	protected function redirect_action() {
		$this->__redirect = func_get_args();
	}

	private function __execute($array) {
		global $conf;

		$controller = $array[0];
		$action = $array[1];
		$params = $array[2];

		$controller_class = Inflector::camelize($controller);
		$class = new $controller_class;
		$controller_class->__set_action($action, $conf->strict_routing);
		$controller_class->__set_params($params);
		return $controller_class->do_action();
	}
	
	protected function use_smarty($setting = true) {
		$this->use_smarty = true;
	}


	// for default configuration
	public function index() {}
}
