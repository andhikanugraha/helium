<?php

// Helium framework
// class HeliumConfiguration
// global $conf;

if (!defined('CONF_PATH'))
	define('CONF_PATH', HE_PATH . '/conf');

class HeliumConfiguration {
	private $__loaded = array();
	private $__array_behaviour = array('paths' => 0,		// replace/append
									   'routes' => 1,		// prepend
									   'backroutes' => 0);	// append

	// paths to folders
	public $paths = array('views' => '/views',
						  'controllers' => '/controllers',
						  'models' => '/models',
						  'plugins' => '/plugins',
						  'smarty' => '/lib/smarty',
						  'smarty_compile' => '/views/_smarty/compile',
						  'smarty_cache' => '/views/_smarty/cache');


	// execution flags
	public $output = true;
	public $production = true;
	public $show_welcome = false;
	public $easy_views = true;

	// canonical URIs - TODO
	public $canonize = true;

	// database
	public $db_type = 'mysql';
	public $db_user = '';
	public $db_pass = '';
	public $db_host = 'localhost';
	public $db_name = '';

	// routing
	public $routes = array();
	public $backroutes = array();
	public $default_controller = 'home';
	public $default_action = 'index';
	public $case_sensitive_routing = false;
	public $strict_routing = false;
	public $use_query_strings = true;
	public $view_pattern = '%s/%s';

	// smarty
	public $use_smarty = true;

	// http response handling
	public $http_version = '1.1';

	// plugins - TODO
	public $load_plugins = true;
	public $plugins = array();
	
	public function __construct() {
		$relative_paths = array('stylesheets', 'javascripts');
		foreach ($this->paths as $key => $value) {
			if (in_array($key, $relative_paths))
				continue;

			if (!file_exists($key))
				$this->paths[$key] = HE_PATH . $value;
		}
	}

	public function load($name) {
		if (strpos($name, '..') !== false)
			return;

		$path = CONF_PATH . "/$name.php";
		if (!file_exists($path))
			return;

		$this->load_file($path);
	}
	
	public function define($key, $value = '') {
		if (!isset($this->$key))
			$this->$key = $value;
	}

	public function load_custom($__array) {
		foreach (array_keys(get_object_vars($this)) as $__key) {
			if (!isset($$__key))
				continue;

			if (!$this->is_public($__key))
				continue;

			$value = $__array[$__key];
			if (is_array($value)) {
				switch ($this->__array_behaviour[$__key]) {
					case 2:
						$this->$__key = array_merge($value, $this->$__key);
						break;
					default:
						$this->$__key = array_merge($this->$__key, $value);
				}
			}
			else
				$this->$__key = $value;
		}
	}
	
	private function load_file($__path) {
		if (!file_exists($__path))
			return;
		
		require_once $__path;
		
		$this->__loaded[] = $__path;

		foreach (array_keys(get_object_vars($this)) as $__key) {
			if (!isset($$__key))
				continue;
				
			if (!$this->is_public($__key))
				continue;

			$value = $$__key;
			if (is_array($value)) {
				switch ($this->__array_behaviour[$__key]) {
					case 2:
						$this->$__key = array_merge($value, $this->$__key);
						break;
					default:
						$this->$__key = array_merge($this->$__key, $value);
				}
			}
			else
				$this->$__key = $value;
		}
	}

	// don't mess with private properties
	private function is_public($key) {
		$reflection = new ReflectionProperty($this, $key);
		return (bool) $reflection->isPublic();
	}
}