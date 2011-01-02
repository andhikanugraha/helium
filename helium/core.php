<?php

// Helium
// Core class

// Core:		Helium::core()
// Database:	Helium::db()
// Config:	Helium::conf([name])

// what does the core class do?
// - provide a global namespace to access essential singletons

final class Helium {
	const version = '0.3';
	const build = 'helium';

	// debug variables
	public static $production = false; // set to false to print out debug info on exceptions
	public static $output = true; // set to false to disable output -- currently not implemented, actually.

	private static $factory_cache = array();

	private static $core;
	private static $db;
	private static $db_handler_name = 'HeliumDB';

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
		if (!self::$core)
			self::$core = new Helium;

		return self::$core;
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
		if (!self::$db) {
			self::$db = new self::$db_handler_name;
			self::$db->db_user = self::conf('db_user');
			self::$db->db_pass = self::conf('db_pass');
			self::$db->db_host = self::conf('db_host');
			self::$db->db_name = self::conf('db_name');
		}

		return self::$db;
	}

	public static function set_db_handler($dbh = 'HeliumDB') {
		if (is_object($dbh))
			self::$db = $dbh;
		elseif (is_string($dbh))
			self::$db_handler_name = $dbh;
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

	public static function load_helium_file($helium_component) {
		require_once HELIUM_PATH . '/' . $helium_component . '.php';
	}

	// Locate where a class might be defined by checking for 'Controller', 'Helper', etc.
	public static function load_class_file($class_name) {
		if (strtolower(substr($class_name, 0, 6)) == 'helium') {
			$helium_component = substr($class_name, 6);
			$filename = Inflector::underscore($helium_component);
			self::load_helium_file($filename);
			return;
		}

		$filename = Inflector::underscore($class_name);
		$last_underscore = strrpos('_', $filename);
		$last_word = substr($underscored, $last_underscore + 1);

		switch($last_word) {
			case 'controller':
			case 'component':
			case 'helper':
				// there can only be one instance of a controller, component, or helper at a time.
				// thus, we can use Helium::factory() instead.
				$name = substr($filename, $last_underscore);
				return (bool) self::factory($last_word, $filename);
			default:
				$success = self::load_app_file('models', $filename);
		}

		return $success;
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

	// recursively strip slashes.
	// taken from WordPress.
	public static function stripslashes_deep($value) {
		$value = is_array($value) ?
					array_map(array(self, 'stripslashes_deep'), $value) :
					stripslashes($value);

		return $value;
	}

}