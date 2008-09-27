<?php

// Helium framework
// bootstrap
error_reporting(E_ALL ^ E_NOTICE);
// constant HE_PATH: the path to the helium folder, where all the files are kept.
// vs. SITE_PATH: the path to the public htdocs/public_html containing index.php
define('HE_PATH', dirname(__FILE__));

// order inclusions by dependency
require_once HE_PATH . '/lib/version.php';		// version identifier
require_once HE_PATH . '/lib/inflector.php';	// inflections

require_once 'lib/configuration.php';
$conf = new HeliumConfiguration;
$conf->load('conf');

require_once HE_PATH . '/lib/exceptions.php';	// exceptions
require_once HE_PATH . '/lib/response.php';		// HTTP response handling
require_once HE_PATH . '/lib/autoload.php';		// autoload

try {
	require_once HE_PATH . '/lib/core.php';		// routing
	require_once HE_PATH . '/lib/database.php';	// db

	// plugins block
	if ($conf->load_plugins) {
		$conf->load('plugins');

		foreach ($conf->plugins as $plugin) {
	 		$path = $conf->paths['plugins'] . "/$plugin.php";
			if (file_exists($path))
				require_once $path;
		}
	}

	$he = new HeliumRouter;
	$response = new HeliumHTTPResponse;
	//$db = new HeliumDatabaseDriver;

	$he->parse_request();
	//echo '<pre>'; print_r($he); exit;


	if (class_exists($he->controller_class)) {
		$controller = new $he->controller_class;
		if (!($controller instanceof HeliumController))
			throw new HeliumException(HeliumException::no_controller);

		$controller->__set_action($he->action);
		$controller->__set_params($he->params);
		$controller->__do_action();

		if ($conf->output)
			$controller->__output($he->params);
	}
	elseif ($conf->output && $conf->show_welcome && $he->controller == $conf->default_controller) {
		require_once HE_PATH . '/lib/views/welcome.php';
		exit;
	}
	elseif (!$he->controller) {
		throw new HeliumException(HeliumException::no_route);
	}
	else {
		if (strlen($he->request) > 1) {
			$boom = explode('/', $he->request);

			while (array_pop($boom) !== null) {
				$dir = implode('/', $boom);
				$dir = SITE_PATH . $dir;
				if ($dir != SITE_PATH && file_exists($dir))
					throw new HeliumException(HeliumException::file_not_found);
			}
		}

		// the easy way
		if ($conf->output && $conf->easy_views && file_exists($request->view_path))
				require_once $request->view_path;
		else
			throw new HeliumException(HeliumException::no_controller);
	}
}
catch (HeliumException $e) {
	if ($conf->output)
		$e->output();
}