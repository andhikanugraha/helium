<?php

class HeliumMap {
	const default_controller = 'home';
	const default_action = 'index';
	const param_prefix = ':';

	public $request;
	public $controller;
	public $action = 'index';
	public $params = array();

	protected $maps = array();

	private $backmaps = array();
	private $default_map = '';
	private $mapped = false;

	public static $controller_class;

	private static $parsed_request = false;
	private static $loaded_maps = array();

	// load the native map file
	// each line has a self::map() statement.
	// if the request matches the mapping, then the following lines will not be mapped.
	// maps are case-insensitive, but variables will remain intact.
	protected function load_map($file = null) {
		if (!$file)
			$file = Helium::conf('core_path') . 'mappings.php';

		require_once $file;

		return $this->mapped;
	}

	private function preg_index($number, $matches = '') {
		return '$' . $matches . '[' . $number . ']';
	}

	// case insensitive
	private function map($path, $controller = '', $action = '', $params = array()) {
		$this->backmap($path, $controller, $action, $params);

		if (func_num_args() == 1 && !$this->default_map)
			$this->default_map = $path;

		if ($this->mapped)
			return;
		
		$act = $action;
		$this->maps[] = func_get_args();

		if (is_int($params))
			$params = array('id' => $params);

		$path = strtolower($path);
		$match = $this->try_to_parse($path);

		if ($match !== false) {
			if ($match['controller']) {
				$controller = $match['controller'];
				unset($match['controller']);
			}
			if ($match['action']) {
				$action = $match['action'];
				unset($match['action']);
			}

			$this->input_params($match);
		}
		else
			return;

		if (!$action)
			$action = self::default_action;

		$this->controller = $controller ? strtolower($controller) : strtolower($match['controller']);
		$this->action = $action ? strtolower($action) : strtolower($match['action']);
		$this->params = $params + $match;
		$this->mapped = true;
		return true;
	}
	
	private function backmap($path, $controller = '', $action = '', $params = array()) {
		if (!$action && !$this->backmaps[$controller]) {
			if (!$this->backmaps[$controller])
				$this->backmaps[$controller] = array();
			$this->backmaps[$controller][0] = $path;
		}
		if ($action && !$this->backmaps[$controller][$action]) {
			if (!$this->backmaps[$controller])
				$this->backmaps[$controller] = array();
			if (!$this->backmaps[$controller][$action])
				$this->backmaps[$controller][$action] = array();
				
			$this->backmaps[$controller][$action][0] = $path;
		}
		if ($action && $params && !$this->backmaps[$controller][$action][$params]) {
			if (!$this->backmaps[$controller])
				$this->backmaps[$controller] = array();
			if (!$this->backmaps[$controller][$action])
				$this->backmaps[$controller][$action] = array();
			$this->backmaps[$controller][$action][serialize($params)] = $path;
		}
	}

	private function try_to_parse($path, $case_sensitive = false) {
		$req = $this->request;
		$req_a = explode('/', $req);
		$path_a = explode('/', $path);
		if (count($req_a) != count($path_a))
			return false;

		$pathinfo = array();
		foreach ($path_a as $key => $value) {
			if ($value[0] == self::param_prefix) {
				$var = substr($value, 1);
				if ($value = $req_a[$key])
					$pathinfo[$var] = $value;
			}
			else {
				$rval = $case_sensitive ? $req_a[$key] : strtolower($req_a[$key]);
				$value = $case_sensitive ? $value : strtolower($value);
				if ($rval != $value)
					return false;
			}
		}

		return $pathinfo;
	}

	public function build_path($controller, $action, $params = array(), $query_string = true) {
		if (!$action)
			$action = self::default_action;

		$maps = array();
		$maps[] = $this->backmaps[$controller][$action][serialize($params)];
		$maps[] = $this->backmaps[$controller][$action][0];
		$maps[] = $this->backmaps[$controller][0];
		$maps[] = $this->default_map;

		settype($params, 'array');
		if ($action == self::default_action)
			$action = '';
		$flags = array('controller' => $controller, 'action' => $action) + $params;

		$mapped = false;
		foreach ($maps as $map) {
			if (!$map || $mapped)
				continue;

			$path_array = array();
			$parts = explode('/', $map);
			foreach ($parts as $part) {
				if ($part[0] == self::param_prefix) {
					$flag = substr($part, 1);
					if ($flags[$flag]) {
						$path_array[] = $flags[$flag];
						unset($flags[$flag]);
					}
					else
						continue 2;
				}
				else
					$path_array[] = $part;
			}
			$mapped = true;
		}
		
		$path = implode('/', $path_array);
		$path = trim($path, '/');
		$path = '/' . $path;

		unset($flags['controller'], $flags['action']);
		if ($flags && $query_string) {
			$query_string = http_build_query($flags);
			$path .= '?' . $query_string;
		}
		return $path;
	}

	public function parse_request() {
		if (self::$parsed_request)
			return true;

		$this->request = $this->fetch_request();
		$this->raw_request = $_SERVER['REQUEST_URI'];

		$mapped = $this->load_map();

		$this->fill_params();

		self::$parsed_request = true;

		if ($mapped)
			return true;
		else
			throw new HeliumException(HeliumException::no_map);
	}

	protected function fetch_request() {
		$self = $_SERVER['PHP_SELF'];
		$self = dirname($self);
		$self = str_replace('\\', '/', $self);
		$self = rtrim($self, '/');

		$req = $_SERVER['REQUEST_URI'];
		$req = substr($req, strlen($self));

		$boom = explode('?', $req);
		$req = $boom[0];

		return $req;
	}
	
	public function request_files() {
		$app_path = Helium::conf('app_path');
		$includes = array();

		$core = Helium::core();

		$controller = $core->controller;

		$controller_path = $app_path . '/' . $controller . '/';
		if (!file_exists($controller_path))
			throw new HeliumException(HeliumException::no_controller);
		
		$controller_functions_path = $controller_path . '_functions.php';
		if (file_exists($controller_functions_path))
			$includes[] = $controller_functions_path;

		$action = $core->action;
		$action_path = $controller_path . $action . '.php';
		if (!file_exists($action_path) || $action[0] == '_')
			throw new HeliumException(HeliumException::no_action);
		else
			$includes[] = $action_path;

		return $includes;
	}

	public function fill_params($params = array()) {
		$this->input_params($params);
		$this->input_params($_GET);
		// $this->input_params($_POST);
	}

	protected function input_params($array) {
		if (is_array($array))
			$this->params = $this->params + $array;
	}
}
