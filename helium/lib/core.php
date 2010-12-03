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
	const version = '1.0'; // this is kinda useless
	const build = 'helium';

	// debug variables
	public static $production = false; // set to false to print out debug info on exceptions
	public static $output = true; // set to false to disable output
	public static $load_plugins = true; // set to false to disable plugins
	public static $canonize = true; // set to false to disable canonical URIs

	public static $app_id = 'helium';
	public static $outputting = false;

	private static $session;

	public $map;
	public $canon;

	public $controller;
	public $action;
	public $params;

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

		if (self::$canonize) {
			$core->canon = new HeliumCanon;
				$core->canon->enforce();
		}
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

	public static function session() {
		static $singleton;

		if (!$singleton)
			$singleton = new HeliumSessions;

		return $singleton;
	}

	public static function params($key = null) {
		$core = self::core();

		$core->map->fill_params();

		if ($key === null)
			return $core->params;
		else
			return $core->params[$key];
	}

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
	
	public static function numval($number) {
		$try = intval($number);
		if ($try >= 2147483647 || $try <= -2147483648) // php's limit
			$try = floatval($number);
		if ($try >= 1.0e+18 || $try <= -1.0e+18)
			$try = $number;
		return $try;
	}
}