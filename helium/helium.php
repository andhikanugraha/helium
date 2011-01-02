<?php

// The Helium framework

define('HELIUM_PATH', dirname(__FILE__));
define('HELIUM_PARENT_PATH', realpath(dirname(__FILE__) . '/../'));
define('HELIUM_APP_PATH', HELIUM_PARENT_PATH . '/app');

/* load files */

// Default configuration
require_once HELIUM_PATH . '/defaults.php';

// Application config
require_once HELIUM_APP_PATH . '/config.php';

// Exceptions
require_once HELIUM_PATH . '/exception.php';

// Helium core
require_once HELIUM_PATH . '/core.php';

// Inflections
require_once HELIUM_PATH . '/inflector.php';

// Autoload
require_once HELIUM_PATH . '/autoload.php';

// everything else is loaded via __autoload().

// Let's begin.
try {

	// sanitize GPC superglobals
	if (get_magic_quotes_gpc()) {
		$_GET = Helium::stripslashes_deep($_GET);
		$_POST = Helium::stripslashes_deep($_POST);
		$_COOKIE = Helium::stripslashes_deep($_COOKIE);
	}

	// boom boom pow
	Helium::init();

}
catch (HeliumException $e) {
	$e->output();
}