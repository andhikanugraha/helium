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

// then comes the exceptions handler
require_once HE_PATH . '/lib/exceptions.php';

try {
	require_once HE_PATH . '/lib/core.php';				// core
	require_once HE_PATH . '/lib/database.php';	// db
	require_once HE_PATH . '/lib/inflector.php';				// inflections
	require_once HE_PATH . '/lib/autoload.php';			// __autoload()
	require_once HE_PATH . '/lib/response.php';

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
	$response = new HeliumHTTPResponse;
	//$db = new HeliumDatabaseDriver;

	$he->parse_request();
	//echo '<pre>'; print_r($he); exit;

	$controller = new $he->controller_class;
	$params = $he->params;

	call_user_func(array($controller, $he->action), $params);

	if ($conf->output)
	$view = $request->view;
	if (file_exists($request->view_path))
		require_once $request->view_path;
}
catch (HeliumException $e) {
	$e->output();
}