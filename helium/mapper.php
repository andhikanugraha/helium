<?php

// The Mapper
// usage:
// $map = new HeliumMapper;
// $map->load_map_file($path_to_map_file);
// $map->parse_request([$raw_request]);

class HeliumMapper {
	public $param_prefix = '%'; // param prefix, has to be PCRE-safe and not be /
	public $param_suffix = '%'; // param suffix, has to be PCRE-safe and not be /

	public $request = ''; // HTTP_REQUEST relative to index.php, with the query string omitted
	public $http_request = '';

	private $map_file = '';

	public $controller = '';
	public $action = '';
	public $params = array('controller' => '', 'action' => '');

	private $maps = array();
	private $backmaps = array();
	private $mapped = false;

	private $parsed_request = false;

	// load the native map file
	// each line has a self::map() statement.
	// if the request matches the mapping, then the following lines will not be mapped.
	private function load_map_file($file = '') {
		if ($this->parsed_request)
			return false;

		elseif (!file_exists($file))
			return false;

		$this->map_file = $file;
		return true;
	}

	private function extend_default_params($params = array()) {
		$default_params = array('controller' => '', 'action' => '');
		return array_merge($default_params, $params);
	}

	// formats is an array of formats that a %particle% has to follow, in PCRE syntax.
	// for example, %year% has to follow the rule \d{4}.
	private function map($map, $params = array(), $formats = array()) {

		// this method does two things:
		// 1. try to parse the map just like an Apache RewriteRule.
		// 2. registers the inverse of the map as a 'backmap'.

		// the syntax is:
		// self::map('/%controller%/%action%/%id%');

		/* assign default values to params */
		$params = $this->extend_default_params($params);

		$this->backmap($params);

		/* convert the map into a PCRE pattern for preg_match */
		$match_search = array();
		$match_replace = array();
		foreach ($formats as $particle => $format) { // custom formats
			$particle = '/' . $this->param_prefix . $particle . self::param_suffix . '/';
			$match_search[] = $particle;
			$match_replace = '(' . $format . ')';
		}
		$match_search[] = '/' . $this->param_prefix . '([\w\[\]]+)' . self::param_suffix . '/'; // default format
		$match_replace[] = '(?P<$1>\w+)';

		// construct the regex pattern to (try to) match
		$match_pattern = preg_replace($match_search, $match_replace, $map);
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

			$this->mapped = true;

			return true;
		}
		// else, does not match. do nothing.

	}

	private function backmap($path, $params = array()) {
		// if action is defined, controller must also be defined.
		// if other parameters are defined, action must also be defined.
		$controller = $params['controller'];
		$action = $params['action'];

		$pure_params = $params;
		unset($pure_params['controller'], $pure_params['action']);
		$serialized_pure_params = $pure_params ? serialize($pure_params) : '';

		if (!is_array($this->backmaps[$controller]))
			$this->backmaps[$controller] = array();
		if (!is_array($this->backmaps[$controller][$action]))
			$this->backmaps[$controller][$action] = array();

		// if controller is blank, let it be ''.
		// if action is blank, let it be ''.
		// if there are no other parameters, let $serialized_pure_params be ''.
		$this->backmaps[$controller][$action][$serialized_pure_params] = $path;
	}

	public function build_path($params = array()) {
		settype($params, 'array'); // enforce $params to be an array

		$controller = $params['controller'];
		$action = $params['action'];

		$pure_params = $this->extend_default_params($params);;
		unset($pure_params['controller'], $pure_params['action']);
		$serialized_pure_params = serialize($pure_params);

		$backmap = $this->backmaps[$controller][$action][$serialized_pure_params];
		if (!$backmap)
			$backmap = $this->backmaps[$controller][$action]['']; // default action backmap
		if (!$backmap)
			$backmap = $this->backmaps[$controller]['']; // default controller backmap
		if (!$backmap)
			$backmap = $this->backmaps['']; // default global backmap

		if ($backmap) {
			$search = array();
			$replace = array();
			foreach ($pure_params as $param => $value) {
				if (strpos($backmap, $param) >= 0) {
					$search[] = $this->param_prefix . $param . self::param_suffix;
					$replace[] = $value;
					unset($pure_params[$param]);
				}
			}

			$built_path = str_replace($search, $replace, $backmap);
			
			// put the unmapped params into the query string portion
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

	public function parse_request($raw_request = '', $map_file = '') {
		if ($this->parsed_request)
			return true;

		if (!$raw_request && $this->raw_request)
			$raw_request = $this->raw_request;
		elseif (!$raw_request)
			$raw_request = $_SERVER['REQUEST_URI'];

		$this->raw_request = $raw_request;
		$this->request = $this->get_request($raw_request);

		if ($this->map_file)
			$this->load_map_file($map_file);

		require $this->map_file;

		$this->fill_params();

		self::$parsed_request = true;

		if ($mapped)
			return true;
		else
			throw new HeliumException(HeliumException::no_map);
	}

}
