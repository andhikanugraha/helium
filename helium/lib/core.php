<?php

// Helium framework
// class Helium
// global $he;

// What is this class for?
// Routing!

// parameter syntax = [param|filter]

final class HeliumCore {
	const version = '1.0'; // this is kinda useless
	const build = 'helium';
	
	const param_prefix = '[';
	const param_suffix = ']';
	const param_filter_sep = '|';
	const verb_delim = '::';
	
	public $request;
	
	public $controller;
	public $controller_class;
	public $action;
	public $params = array();

	public $routed;
	public $backroutes = array();
	private $default_route;
	
	private $paths_cache = array();

	public function __construct() {
//		HeliumPlugins::apply_hooks(__METHOD__);
		
		$this->request = $this->get_request();
	}
	
	public function get_request($req = '') {
		$self = $_SERVER['PHP_SELF'];
		$self = dirname($self);
		$self = str_replace('\\', '/', $self);
		$self = rtrim($self, '/');
		if (!$self)
			$self = '/';

		if (!$req)
			$req = $_SERVER['REQUEST_URI'];
		$req = substr($req, strlen($self));
		$req = '/' . $req;

		$boom = explode('?', $req);
		$req = $boom[0];

		return $req;
	}

	// routing code below
	// case insensitive matching

	public function parse_request() {
		global $conf;
		
		$conf->load('routes');
		$conf->load('backroutes');
		
		foreach ($conf->routes as $path => $predicate) {
			if (is_int($path)) {
				$path = $predicate;
				$this->parse_route($path);

				$this->parse_backroute($path);
			}
			elseif (is_array($predicate)) {
				$this->parse_route($path, $predicate[0], $predicate[1]);

				$this->parse_backroute($path, $predicate[0]);
			}
			else {
				$this->parse_route($path, $predicate);

				$this->parse_backroute($path, $predicate);
			}
		}

		if (!$this->controller)
			$this->controller = $conf->default_controller;
		if (!$this->action)
			$this->action = $conf->default_action;
		$this->view = $this->controller . '/' . $this->action;
		$this->controller_class = Inflector::camelize($this->controller . '_controller');

//		HeliumPlugins::apply_hooks(__METHOD__, func_get_args());
	}
	
	private function parse_route($path, $verb = '', $params = array()) {
		if ($this->routed)
			return;

		if (!$verb && !$this->default_route)
			$this->default_route = $path;

		if (!$verb)
			$controller = '';
		elseif (strpos($verb, self::verb_delim) === false)
			$controller = $verb;
		else {
			$verb = explode(self::verb_delim, $verb);
			$controller = $verb[0];
			$action = $verb[1];
		}

		if (is_int($params))
			$params = array('id' => $params);

		$path = strtolower($path);
		$match = $this->try_to_parse($path);

		if ($match !== false) {
			$this->input_params($match);
		}

		if ($match !== false) {
			$this->controller = $controller ? strtolower($controller) : strtolower($match['controller']);
			$this->action = $action ? strtolower($action) : strtolower($match['action']);
			$this->routed = true;
		}

		return true;
	}
	
	private function parse_backroute($path, $verb = '') {
		if (!$verb)
			return;

		elseif (strpos($verb, self::verb_delim) === false)
			$controller = $verb;
		else {
			$verb = explode(self::verb_delim, $verb);
			$controller = $verb[0];
			$action = $verb[1];
		}

		if ($controller && !$action && !$this->backroutes[$controller][0]) {
			if (!$this->backroutes[$controller])
				$this->backroutes[$controller] = array();
			$this->backroutes[$controller][] = $path;
		}
		if ($controller && $action && !$this->backroutes[$controller][$action]) {
			if (!$this->backroutes[$controller])
				$this->backroutes[$controller] = array();
			if (!$this->backroutes[$controller][$action])
				$this->backroutes[$controller][$action] = array();
			$this->backroutes[$controller][$action][] = $path;
		}
	}
	
	private function get_param_name($string) {
		if (!(substr($string, 0, 1) == self::param_prefix && substr($string, -1) == self::param_suffix))
			return false;

		$param = substr($string, 1, -1);
		$pos = strpos($param, self::param_filter_sep);

		if ($pos !== false)
			return substr($param, 0, $pos);
		else
			return $param;
	}
	
	private function get_param_filter($string) {
		if (!(substr($string, 0, 1) == self::param_prefix && substr($string, -1) == self::param_suffix))
			return false;

		$param = substr($string, 1, -1);
		
		if (($pos = strpos($param, self::param_filter_sep)) !== false)
			return substr($param, $pos + 1);
		else
			return false;
	}

	private function parse_breadcrumb($crumb, $value, $case_sensitive = false) {
		$param_name = $this->get_param_name($crumb);

		$filter = $this->get_param_filter($crumb);
		if (!$filter) {
			if ($param_name == 'controller' || $param_name == 'action')
				$filter_grep = "/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/";
			else
				return $param_name;
		}
		
		if (is_array($value))
			$value = $value[$param_name];
		else
			$value = (string) $value;
		
		if (!$case_sensitive) {
			$value = strtolower($value);
			$filter = strtolower($filter);
		}

		if (!$filter_grep) {
			$filter = str_replace('n', '([0-9])', $filter);
			$filter_grep = "/^$filter$/";
		}
		$extracted = preg_match($filter_grep, $value);

		if ($extracted) // matches the filter!
			return $param_name;
		else
			return false;
	}

	private function try_to_parse($path, $case_sensitive = false) {
		$req = $this->get_request();
		$req_a = explode('/', $req);
		$path_a = explode('/', $path);
		$pathinfo = array();
		foreach ($path_a as $key => $value) {
			if ($var = $this->parse_breadcrumb($value, $req_a[$key])) { // it was a param and passed the filter (if any)
				$pathinfo[$var] = $req_a[$key];
			}
			elseif ($this->get_param_name($value)) { // so it was a param, but didn't pass the filter
				return false;
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

	private function input_params($array) {
		if (is_array($array))
			$this->params = array_merge($this->params, $array);
	}

	public function calculate_path($controller, $action = '', $params = array(), $query_string = true) {
		if ($cache = $this->paths_cache[serialize(func_get_args())])
			return $cache;

		global $conf;

		if (!$action)
			$action = $conf->default_action;

		$apex = array('controller' => $controller, 'action' => $action);
		$params_a = array_merge($params, $apex);
		
		if(!($paths = $this->backroutes[$controller][$action]))
			$paths = array();

		if (!$paths)
			$paths = array_merge($paths, $this->backroutes[$controller]);
		if (!$paths)
			$paths = array($this->default_route);
		
		$parsed_paths = array();
		$matches = array();
		$path = '';
		foreach ($paths as $key => $value) {
			list($matches, $parsed_path) = $this->try_path($value, $params_a);
			// this means that all the parameters match the path -> definite match!
			if ($matches === array_keys($params)) {
				$path = $parsed_path;
				break;
			}
			else {
				$count = count($matches);
				if ($parsed_paths[$count] || $matches[$count]) // take the first definition
					continue;

				$parsed_paths[$count] = $parsed_path;
				$matches[$count] = $matches;
			}
		}	
		if (!$path) {
			krsort($parsed_paths);
			krsort($matches);
			$path = reset($parsed_paths);
			$mapped_params = reset($matches);
		}
		if (!is_array($mapped_params))
			$mapped_params = array();

		$unmapped_params = array();
		foreach (array_diff(array_keys($params), $mapped_params) as $param) {
			$unmapped_params[$param] = $params[$param];
		}

		$path = trim($path, '/');
		$path = '/' . $path;

		if ($unmapped_params && $query_string) {
			$query_string = http_build_query($unmapped_params);
			$path .= '?' . $query_string;
		}

		$this->paths_cache[serialize(func_get_args())] = $path;

		return $path;
	}

	private function try_path($path, $params) {
		$crumbs = explode('/', $path);
		$param_matches = array();
		$parsed_path = array();

		foreach ($crumbs as $crumb) {
			// since $params is an array, so parse_breadcrumb will find $params[$var]
			if ($var = $this->parse_breadcrumb($crumb, $params)) {
				$a = $this->get_param_name($crumb);
				$param_matches[] = $var;
				$parsed_path[] = $params[$var];
			}
			// so it was a parameter, but it didn't pass the filter
			elseif ($var = $this->get_param_name($crumb)) {
				$params[$var] = '';
				$parsed_path = implode($parsed_path, '/');
				$parsed_path = trim($parsed_path, '/');
				$parsed_path = '/' . $parsed_path;

				return array($param_matches, $parsed_path);
			}
			else {
				$parsed_path[] = $crumb;
			}
		}

		$parsed_path = implode($parsed_path, '/');
		$parsed_path = trim($parsed_path, '/');
		$parsed_path = '/' . $parsed_path;

		return array($param_matches, $parsed_path);
	}
}

