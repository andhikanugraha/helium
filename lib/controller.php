<?php

abstract class Helium_Controller {
	protected $params;
	protected $__action = '';
	protected $__view = '';

	private $__forbidden_words = array('__forbidden_words', 'smarty');
	private $__switched_action;
	private $__controller;
	private $__content;

	protected $output = 'smarty';
	protected $smarty_layout = '';

	protected $content_type = '';

	// use this as constructor for descendant classes
	protected function __build() {}

	public function __construct() {
		global $conf;
		$this->__controller = preg_replace('|Controller$|', '', get_class($this));
		$this->__controller = Inflector::underscore($this->__controller);
		$this->__action = $conf->default_action;
		$this->__build();
	}

	public function __set_action($action = '', $strict = false) {
		if ($action)
			$this->__action = $this->__view = $action;

		$router->action = $this->__action;
		return $this;
	}

	public function __set_params($params = array()) {
		if ($params && !is_array($params))
			$params = array($params);
		$this->params = $params;
		return $this;
	}

	public function __do_action() {
		if (!method_exists($this, $this->__action))
			throw new Helium_Exception(Helium_Exception::no_action);

		$router->controller = $this->__controller;

		$this->__content = $this->{$this->__action}();

		if ($this->__switched_action)
			return $this->__execute($this->__switched_action);

		return $this;
	}

	public function __output() {
		global $response;
		$response->set_response_code($this->response_code);

		global $conf;
		if (!$this->__view_path)
			$this->__view_path = sprintf($conf->view_pattern, $this->__controller, $this->__view);

		if ($this->output != 'smarty') {
			if ($this->output == 'php') {
				if (file_exists($file = $conf->paths['views'] . '/' . $this->__view_path . '.php'))
					require_once $file;
				else
					throw new Helium_Exception(Helium_Exception::no_view);
			}
		}

		switch ($this->output) {
			case 'smarty':
				if (!$conf->use_smarty)
					return;

				global $smarty;
				$smarty = new SmartyOnHelium;
				$smarty->set_body($this->__view_path);
				if ($this->smarty_layout)
					$smarty->set_layout($this->smarty_layout);

				$smarty->import($this);

				$smarty->yell();
				break;
			case 'php':
				if (file_exists($file = $this->__view_path . '.php'))
					require_once $file;
				else
					throw new Helium_Exception(Helium_Exception::no_view);
				break;
			case 'json':
				$response->set_content_type('json');
				echo json_encode($this->__content);
				break;
			default:
				if ($this->content_type)
					$response->set_content_type($this->content_type);
				echo $this->__content;
		}

		return true;
	}

	public function __yell($action = '', $params = array()) {
		$this->__set_action($action);
		$this->__do_action()->__output();
	}

	// switch_action: execute different action but under the same route
	// the view is changed as well.
	protected function switch_action($controller, $action = '', $params = array()) {
		if (is_object($controller) && get_class($controller) == get_class($this))
			$controller = $this->__controller;
		$this->__switched_action = array($controller, $action, $params);
	}

	// switch_action: do a http redirection to another action
	protected function redirect_action($action, $params = array()) {
		global $router, $response;
		$args = array($this->__controller, $action, $params);
		$path = call_user_func_array(array($router, 'resolve_path'), $args);

		return $response->redirect($path);
	}

	private function __execute($array) {
		global $conf;

		$controller = $array[0];
		$action = $array[1];
		$params = $array[2];

		$controller_class = Inflector::camelize($controller . '_controller');
		$controller_class = new $controller_class;
		if (!($controller_class instanceof Helium_Controller)) {
			global $router;
			$router->controller = $controller;
			throw new Helium_Exception(Helium_Exception::no_controller);
		}
		if ($action)
			$controller_class->__set_action($action, $conf->strict_routing);
		$controller_class->__set_params($params);
		return $controller_class->__do_action();
	}

	protected function use_smarty($setting = true) {
		$this->use_smarty = $setting;
	}

	// for default configuration
	public function index() {}
}
