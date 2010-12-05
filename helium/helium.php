<?php

$start = microtime();

// Helium

if (get_magic_quotes_gpc()) {
	function stripslashes_deep($value) {
	    $value = is_array($value) ?
	                array_map('stripslashes_deep', $value) :
	                stripslashes($value);

	    return $value;
	}
	$_GET = stripslashes_deep($_GET);
	$_POST = stripslashes_deep($_POST);
	$_COOKIE = stripslashes_deep($_COOKIE);
}

define('HELIUM_PATH', dirname(__FILE__));
define('HELIUM_PARENT_PATH', realpath(dirname(__FILE__) . '/../'));
define('HELIUM_APP_PATH', HELIUM_PARENT_PATH . '/app');

// File inclusion list
require_once HELIUM_PATH . '/exception.php';	// exceptions
require_once HELIUM_PATH . '/core.php';			// core
require_once HELIUM_PATH . '/inflector.php';	// inflections
require_once HELIUM_PATH . '/mapper.php';		// map
require_once HELIUM_PATH . '/db.php';			// database
require_once HELIUM_PATH . '/autoload.php';		// __autoload()

// config
require_once HELIUM_PATH . '/default_config.php';		// default configuration
require_once HELIUM_APP_PATH . '/config.php';

try {
	Helium::init();
}
catch (HeliumException $e) {
	$e->output();
}