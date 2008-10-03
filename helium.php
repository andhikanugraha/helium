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
$conf = new Helium_Configuration;
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

	$router = new Helium_Router;
	$response = new Helium_HTTPResponse;
	$db = new Helium_DatabaseDriver;

	$router->parse_request();
	// echo '<pre>'; print_r($router); exit;

	$controller_name = $router->params['controller'];
	$controller_class = Inflector::classify($controller_name . '_controller');

	if (class_exists($controller_class)) {
		$controller_object = new $controller_class;
		if (!($controller_object instanceof Helium_Controller))
			throw new Helium_Exception(Helium_Exception::no_controller);
		if (!($router->params['action']))
			$router->params['action'] = $conf->default_action;
		$controller_object->__set_action($router->params['action']);
		$controller_object->__set_params($router->params);
		$class = $controller_object->__do_action();

		if ($conf->output)
			$class->__output();
	}

	// easy views
	elseif ($conf->output && $conf->easy_views) {
		$view = sprintf($conf->view_pattern, $controller_name, $router->params['action']);
		$view = trim($view, '/');
		$view = $conf->paths['views'] . '/' . $view;
		if (file_exists($view))
			require_once $view . '.php';
	}

	// welcome to helium page
	elseif ($conf->output && $conf->show_welcome && $controller_name == $conf->default_controller) {
		require_once HE_PATH . '/lib/views/welcome.php';
		exit;
	}
	elseif (!$controller_name) {
		throw new Helium_Exception(Helium_Exception::no_route);
	}
	else {
		if (strlen($router->request) > 1) {
			$boom = explode('/', $router->request);

			while (array_pop($boom) !== null) {
				$dir = implode('/', $boom);
				$dir = SITE_PATH . $dir;
				if ($dir != SITE_PATH && file_exists($dir))
					throw new Helium_Exception(Helium_Exception::file_not_found);
			}
		}

		throw new Helium_Exception(Helium_Exception::no_controller);
	}
}
catch (Helium_Exception $e) {
	if ($conf->output)
		$e->output();
}