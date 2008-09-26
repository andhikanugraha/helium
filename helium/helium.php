<?php

// Helium framework
// bootstrap
error_reporting(E_ALL ^ E_NOTICE);
// constant HE_PATH: the path to the helium folder, where all the files are kept.
// vs. SITE_PATH: the path to the public htdocs/public_html containing index.php
define('HE_PATH', dirname(__FILE__));

// configuration goes before anything
require_once 'lib/configuration.php';
$conf = new HeliumConfiguration;
$conf->load('conf');

require_once HE_PATH . '/lib/core.php';				// core
// require_once HE_PATH . '/lib/helium_database_driver.php';	// db
require_once HE_PATH . '/lib/inflector.php';				// inflections
require_once HE_PATH . '/lib/autoload.php';			// __autoload()

// plugins block
if ($conf->load_plugins) {
	$conf->load('plugins');

	foreach ($conf->plugins as $plugin) {
 		$path = $conf->paths['plugins'] . "/$plugin.php";
		if (file_exists($path))
			require_once $path;
	}
}

$he = new HeliumCore;
//$db = new HeliumDatabaseDriver;

$he->parse_request();
echo '<pre>'; print_r($he);
/*
$controller = new $he->controller_class;
$action = $he->action;
$params = $he->params;

call_user_func(array($controller, $action), $params);

if ($conf->output)
$view = $request->view;
require_once $request->view_path;
*/