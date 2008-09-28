<?php

abstract class Helium_Controller {
	protected $__params;
	protected $__action = '';

	private $__forbidden_words = array('__forbidden_words', 'smarty');
	private $__switched_action;

	protected $use_smarty = true;
	protected $smarty_layout = '';
	
	protected $content_type = '';

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
			throw new Helium_Exception(Helium_Exception::no_action);

		$try = $this->{$this->__action}();
		
		if ($this->__switched_action)
			return $this->__execute($this->__switched_action);

		return $this;
	}

	public function __output() {
		global $response;
		$response->set_response_code($this->response_code);
		
		if ($this->content_type)
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
			
		$smarty->import($this);

		$smarty->yell();
	}

	public function __yell($action = '', $params = array()) {
		$this->__set_action($action);
		$this->__do_action()->__output();
	}
	
	// switch_action: execute different action but under the same route
	protected function switch_action() {
		$this->__switched_action = func_get_args();
	}
	
	// switch_action: do a http redirection to another action
	protected function redirect_action() {
		global $router;
		$path = call_user_func_array(array($router, 'resolve_path'), func_get_args());
		
		return $response->redirect($path);
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
		$this->use_smarty = $setting;
	}


	// for default configuration
	public function index() {}
}
