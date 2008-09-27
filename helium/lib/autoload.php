<?php

// Helium framework
// function __autoload

function __autoload($class_name) {
	global $conf;

	$file_name = '/';
	$file_name .= Inflector::underscore($class_name);
	$file_name .= '.php';

	if (!$conf)
		$conf = new HeliumConfiguration;
	try {
		if (preg_match('/Controller$/', $class_name)) {
			$controller = $conf->paths['controllers'] . $file_name;
			if (file_exists($controller)) {
				require_once $controller;
				return;
			}

			if (Inflector::underscore($class_name) == $conf->default_controller . '_controller') {
				require_once HE_PATH . '/lib/views/welcome.php';
				exit;
			}
		}

		$model = $conf->models . $file_name;
		if (file_exists($model)) {
			require_once $model;
			return;
		}

		$code = $controller ? HeliumException::no_controller : HeliumException::no_model;
		throw new HeliumException($code, $class_name);
	}
	catch (HeliumException $e) {
		$e->output();
	}
}
