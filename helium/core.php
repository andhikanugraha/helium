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

// TODO: merge HeliumMap with Helium core.

final class Helium {
	const version = '1.0'; // this is kinda useless
	const build = 'helium';

	// debug variables
	public static $production = false; // set to false to print out debug info on exceptions
	public static $output = true; // set to false to disable output -- currently not implemented, actually.

	public $map;

	public $controller;
	public $action;
	public $params;

	private static $factory_cache = array();

	private function __construct() {
	}

	private function __clone() {}

	public static function core() {
		static $instance;

		if (!$instance)
			$instance = new Helium;

		return $instance;
	}

	public static function init() {
		static $initiated = false;
		if ($initiated)
			return;

		$core = self::core();

		if (!$core->map)
			$core->map = new HeliumMap;
		$core->map->parse_request();
		$core->request = $core->map->request;
		$core->controller = $core->map->controller;
		$core->action = $core->map->action;
		$core->params = $core->map->params;
	}

	// singletons

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
			require_once self::get_app_file_path($directory, $filename);
			return true;
		}
		else
			return false;
	}

	public static function factory($type, $name) {
		if ($object = self::$factory_cache[$name])
			return $object;

		$directory = Inflector::pluralize($type);
		$joined = $name . '_' . $type;

		// load the class definition file. if it doesn't exist, throw exception
		if (!self::load_app_file($directory, $joined))
			throw new HeliumException(constant('HeliumException::no_' . $type), $name);

		$class_name = Inflector::camelize($joined);

		$object = self::$factory_cache[$name] = new $class_name;

		return $object;
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