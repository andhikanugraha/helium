<?php

// The Helium framework

define('HELIUM_PATH', dirname(__FILE__));
define('HELIUM_PARENT_PATH', realpath(dirname(__FILE__) . '/../'));
define('HELIUM_APP_PATH', HELIUM_PARENT_PATH . '/app');

/* load files */

// Exceptions
require_once HELIUM_PATH . '/exception.php';

// Helium core
require_once HELIUM_PATH . '/core.php';

// Inflections
require_once HELIUM_PATH . '/inflector.php';

// Mapper
require_once HELIUM_PATH . '/mapper.php';

// DB
require_once HELIUM_PATH . '/db.php';

// Models/Active Records
require_once HELIUM_PATH . '/autoload.php';
require_once HELIUM_PATH . '/record.php';
require_once HELIUM_PATH . '/record_set.php';

// Views and Helpers
require_once HELIUM_PATH . '/helper.php';

// Controllers and Components
require_once HELIUM_PATH . '/controller.php';
require_once HELIUM_PATH . '/component.php';

// Default configuration
require_once HELIUM_PATH . '/defaults.php';

// Load the application's config
require_once HELIUM_APP_PATH . '/config.php';

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