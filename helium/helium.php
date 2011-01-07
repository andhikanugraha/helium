<?php

// The Helium framework

define('HELIUM_PATH', dirname(__FILE__));
define('HELIUM_PARENT_PATH', realpath(dirname(__FILE__) . '/../'));
define('HELIUM_APP_PATH', HELIUM_PARENT_PATH . '/app');

/* load files */

// Helium core
require_once HELIUM_PATH . '/core.php';

// Autoload
require_once HELIUM_PATH . '/autoload.php';

// everything else is loaded via __autoload().

// Let's begin.
try {

	// boom boom pow
	Helium::init();

}
catch (HeliumException $e) {
	$e->output();
}