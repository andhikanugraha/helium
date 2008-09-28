<?php

// Helium framework
// bootstrap

$start = microtime();

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
	require_once HE_PATH . '/lib/router.php';	// routing
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

	$router = new HeliumRouter;
	$response = new HeliumHTTPResponse;
	//$db = new HeliumDatabaseDriver;

	$router->parse_request();
	//echo '<pre>'; print_r($router); exit;

	if (class_exists($router->controller_class)) {
		$controller = new $router->controller_class;
		if (!($controller instanceof HeliumController))
			throw new HeliumException(HeliumException::no_controller);

		$controller->__set_action($router->action);
		$controller->__set_params($router->params);
		$class = $controller->__do_action();

		if ($conf->output)
			$class->__output();
	}

	// easy views
	elseif ($conf->output && $conf->easy_views && file_exists($router->view_path . '.php'))
		require_once $router->view_path . '.php';

	// welcome to helium page
	elseif ($conf->output && $conf->show_welcome && $router->controller == $conf->default_controller) {
		require_once HE_PATH . '/lib/views/welcome.php';
		exit;
	}
	elseif (!$router->controller) {
		throw new HeliumException(HeliumException::no_route);
	}
	else {
		if (strlen($router->request) > 1) {
			$boom = explode('/', $router->request);

			while (array_pop($boom) !== null) {
				$dir = implode('/', $boom);
				$dir = SITE_PATH . $dir;
				if ($dir != SITE_PATH && file_exists($dir))
					throw new HeliumException(HeliumException::file_not_found);
			}
		}

		throw new HeliumException(HeliumException::no_controller);
	}
}
catch (HeliumException $e) {
	if ($conf->output)
		$e->output();
}