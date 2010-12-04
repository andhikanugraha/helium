<?php

$start = microtime();

// Helium (minus Smarty)

if (get_magic_quotes_gpc()) {
	function stripslashes_deep($value)
	{
	    $value = is_array($value) ?
	                array_map('stripslashes_deep', $value) :
	                stripslashes($value);

	    return $value;
	}
	$_GET = stripslashes_deep($_GET);
	$_POST = stripslashes_deep($_POST);
	$_COOKIE = stripslashes_deep($_COOKIE);
}

// File inclusion list
require_once dirname(__FILE__) . '/lib/exceptions.php';	// exceptions
require_once dirname(__FILE__) . '/lib/inflections.php';	// inflections
require_once dirname(__FILE__) . '/lib/map.php';			// map
require_once dirname(__FILE__) . '/lib/core.php';		// core
require_once dirname(__FILE__) . '/lib/canon.php';		// canonical URLs
require_once dirname(__FILE__) . '/lib/database.php';	// database
require_once dirname(__FILE__) . '/lib/sessions.php';	// sessions
require_once dirname(__FILE__) . '/lib/autoload.php';	// __autoload()

// config
require_once dirname(__FILE__) . '/lib/defaults.php';		// default configuration
require_once dirname(__FILE__) . '/helium_configuration.php';


try {
	Helium::init();
	// application-specific functions
	if ( file_exists(dirname(__FILE__) . '/app/_functions.php') )
		require_once dirname(__FILE__) . '/app/_functions.php';

	// maintain global namespace here
	foreach (HeliumMap::request_files() as $file)
		require_once $file;
}
catch (HeliumException $e) {
	$e->output();
}