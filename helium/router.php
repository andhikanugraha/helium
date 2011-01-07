<?php

// HeliumRouter
// Routing class for Helium
// Parses an HTTP request as a set of parameters

class HeliumRouter {
	public $param_prefix = '%'; // param prefix, has to be PCRE-safe and not be /
	public $param_suffix = '%'; // param suffix, has to be PCRE-safe and not be /

	public $request = ''; // HTTP_REQUEST relative to index.php, with the query string omitted
	public $http_request = '';

	public $controller = '';
	public $action = '';
	public $params = array('controller' => '', 'action' => '');

	private $routes_file = '';
	private $routes = array();
	private $backroutes = array();
	private $routed = false;

	private $parsed_request = false;

	public function load_routes_file($file = '') {
		if ($this->parsed_request)
			return false;

		elseif (!file_exists($file))
			return false;

		$this->routes_file = $file;
		return true;
	}

	private function extend_default_params($params = array()) {
		$default_params = array('controller' => '', 'action' => '');

		return array_merge($default_params, $params);
	}

	// formats is an array of formats that a %particle% has to follow, in PCRE syntax.
	// for example, %year% has to follow the rule \d{4}.
	public function route($route, $params = array(), $formats = array()) {
		// assign default values to params
		$params = $this->extend_default_params($params);

		$this->backroute($route, $params);

		if ($this->routed)
			return;
		else
			return $this->attempt_route($route, $params, $formats);
	}

	// attempt a route
	private function attempt_route($route, $params = array(), $formats = array()) {
		$this->routes[] = $route;

		/* convert the route into a PCRE pattern for preg_match */
		$match_search = array('-\?-');
		$match_replace = array('-\?-');
		foreach ($formats as $particle => $format) { // custom formats
			$particle = '/' . $this->param_prefix . $particle . $this->param_suffix . '/';
			$match_search[] = $particle;
			$match_replace = '(' . $format . ')';
		}
		$match_search[] = '/' . $this->param_prefix . '([\w\[\]]+)' . $this->param_suffix . '/'; // default format
		$match_replace[] = '(?P<$1>\w+)';

		// construct the regex pattern to (try to) match
		$match_pattern = preg_replace($match_search, $match_replace, $route);
		$match_pattern = rtrim($match_pattern, '/');
		$match_pattern .= '/?'; // optional trailing slash
		$match_pattern = '#^' . $match_pattern . '$#'; // the ^ and $ ensures that the whole string is matched

		// try to match $this->request against $match_pattern
		$matches = array();
		if (preg_match($match_pattern, $this->request, $matches)) { // we have a match!
			$matched_params = array();
			foreach ($matches as $key => $value) {
				if (is_string($key)) // named param
					$matched_params[$key] = $value;
			}

			$all_params = array_merge($params, $matched_params);

			$this->params = $all_params;
			$this->controller = $all_params['controller'];
			$this->action = $all_params['action'];

			$this->routed = true;

			return true;
		}
		
		// route does not match request.
		return false;
	}

	private function backroute($path, $params = array()) {
		// if action is defined, controller must also be defined.
		// if other parameters are defined, action must also be defined.
		$controller = $params['controller'];
		$action = $params['action'];

		$pure_params = $params;
		unset($pure_params['controller'], $pure_params['action']);
		$serialized_pure_params = $pure_params ? serialize($pure_params) : '';

		if (!is_array($this->backroutes[$controller]))
			$this->backroutes[$controller] = array();
		if (!is_array($this->backroutes[$controller][$action]))
			$this->backroutes[$controller][$action] = array();

		// if controller is blank, let it be ''.
		// if action is blank, let it be ''.
		// if there are no other parameters, let $serialized_pure_params be ''.
		$this->backroutes[$controller][$action][$serialized_pure_params] = $path;
	}

	public function build_path($params = array()) {
		settype($params, 'array'); // enforce $params to be an array

		$controller = $params['controller'];
		$action = $params['action'];

		$pure_params = $this->extend_default_params($params);;
		unset($pure_params['controller'], $pure_params['action']);
		$serialized_pure_params = serialize($pure_params);

		$backroute = $this->backroutes[$controller][$action][$serialized_pure_params];
		if (!$backroute)
			$backroute = $this->backroutes[$controller][$action]['']; // default action backroute
		if (!$backroute)
			$backroute = $this->backroutes[$controller]['']; // default controller backroute
		if (!$backroute)
			$backroute = $this->backroutes['']; // default global backroute

		if ($backroute) {
			$search = array();
			$replace = array();
			foreach ($pure_params as $param => $value) {
				if (strpos($backroute, $param) >= 0) {
					$search[] = $this->param_prefix . $param . $this->param_suffix;
					$replace[] = $value;
					unset($pure_params[$param]);
				}
			}

			$built_path = str_replace($search, $replace, $backroute);
			
			// put the unrouteped params into the query string portion
			$query_string = http_build_query($pure_params);

			return $built_path . '?' . $query_string;
		}
	}

	private function get_request() {
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

	public function fill_params($params = array()) {
		$this->input_params($params);
		$this->input_params($_GET);
	}

	private function input_params($array = array()) {
		if (is_array($array))
			$this->params = array_merge($this->params, $array);
	}

	public function parse_request($raw_request = '', $routes_file = '') {
		if ($this->parsed_request)
			return true;

		if (!$raw_request && $this->raw_request)
			$raw_request = $this->raw_request;
		elseif (!$raw_request)
			$raw_request = $_SERVER['REQUEST_URI'];

		$this->raw_request = $raw_request;
		$this->request = $this->get_request($raw_request);

		if ($routes_file)
			$this->load_routes_file($routes_file);

		$router = $this;
		$route = function() use ($router) {
				$args = func_get_args();
				call_user_func_array(array($router, 'route'), $args);
			};

		require $this->routes_file;

		$this->fill_params();

		$this->parsed_request = true;

		if ($this->routed)
			return true;
		else
			throw new HeliumException(HeliumException::no_route, $raw_request, $this->routes_file);
	}

}
