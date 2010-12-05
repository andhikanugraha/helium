<?php

// Helium
// Core library

// Core class:		Helium::core()
// Database class:	Helium::db()
// Config class:	Helium::conf([name])
// Smarty class:	Helium::view()
// Session class:	Helium::session()

// what does the core class do?
// - provide a global namespace to access essential singletons

final class Helium {
	const version = '0.2';
	const build = 'helium';

	// debug variables
	public static $production = false; // set to false to print out debug info on exceptions
	public static $output = true; // set to false to disable output -- currently not implemented, actually.

	private static $factory_cache = array();

	public $map;

	// the three properties below aren't really used, actually
	public $controller = '';
	public $action = '';
	public $params = array();
	
	public $controller_object;

	private function __construct() {
		$this->map = new HeliumMapper;
	}

	private function __clone() {}

	public static function init($map_file = '') {
		static $initiated = false;
		if ($initiated)
			return;

		$core = self::core();

		// reset the mapper
		$core->map = new HeliumMapper;
		if (!$map_file)
			$map_file = self::conf('app_path') . '/map.php';
		if (!$core->map->load_map_file($map_file))
			throw new HeliumException(HeliumException::no_map_file, $map_file);

		$core->map->parse_request();
		$core->request = &$core->map->request;
		$core->controller = &$core->map->controller;
		$core->action = &$core->map->action;
		$core->params = &$core->map->params;

		// load the controller and execute it
		$core->controller_object = self::factory('controller', $core->map->controller);
		$core->controller_object->action = $core->map->action;
		$core->controller_object->params = $core->map->params;
		call_user_func($core->controller_object);
	}

	// singletons

	public static function core() {
		static $instance;

		if (!$instance)
			$instance = new Helium;

		return $instance;
	}

	public static function conf($var = '') {
		static $conf;
		if (!$conf)
			$conf = new HeliumConfiguration;

		if ($var)
			return $conf->$var;
		else
			return $conf;
	}

	public static function db() {
		static $db;

		if (!$db) {
			$conf = self::conf();
			$db = new HeliumDB;
			$db->configure($conf->db_user, $conf->db_pass, $conf->db_name, $conf->db_host);
		}

		return $db;
	}

	// App-handling methods

	public static function get_app_file_path($directory, $filename) {
		$base_path = self::conf($directory . '_path');

		return $base_path . '/' . $filename . '.php';
	}

	// Load an app's file.
	// Since we're not in the global scope, this function is only useful
	// for files that only contain class definitions.
	public static function load_app_file($directory, $filename) {
		$full_path = self::get_app_file_path($directory, $filename);

		if (file_exists($full_path)) {
			require_once $full_path;
			return true;
		}
		else
			return false;
	}

	// factory for app objects
	public static function factory($type, $name) {
		$joined = $name . '_' . $type;

		if ($object = self::$factory_cache[$joined])
			return $object;

		$directory = Inflector::pluralize($type);

		// load the class definition file. if it doesn't exist, throw exception
		if (!self::load_app_file($directory, $joined))
			throw new HeliumException(constant('HeliumException::no_' . $type), $name);

		$class_name = Inflector::camelize($joined);

		$object = self::$factory_cache[$name] = new $class_name;

		return $object;
	}

	// some useful, generic functions

	public static function numval($number) {
		$try = intval($number);
		if ($try >= 2147483647 || $try <= -2147483648) // php's limit
			$try = floatval($number);
		if ($try >= 1.0e+18 || $try <= -1.0e+18)
			$try = $number;
		return $try;
	}

	public static function get_public_methods($class) {
		return get_class_methods($class);
	}

	// recursively strip slashes.
	// taken from WordPress.
	public static function stripslashes_deep($value) {
		$value = is_array($value) ?
					array_map(array(self, 'stripslashes_deep'), $value) :
					stripslashes($value);

		return $value;
	}

	// --- deprecated functions. or rather, functions that will be moved somewhere else.

	public static function redirect() {
		$base_uri = self::conf('base_uri');
		if (func_num_args() == 1) {
			$target = func_get_arg(0);
			if (strpos($target, '://') < 0) // relative URL
				$target = $base_uri . $target;
		}
		else {
			$core = self::core();
			$args = func_get_args();
			$target = $base_uri . $core->map->build_path($args[0], $args[1], $args[2]);
		}

		if (!headers_sent()) {
			@header("Location: $target");
			exit;
		}
		else
			throw new HeliumException(HeliumException::failed_to_redirect, $target);
	}
}