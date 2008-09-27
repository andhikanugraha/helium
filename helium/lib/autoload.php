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

	if (preg_match('/Controller$/', $class_name)) {
		$controller = $conf->paths['controllers'] . $file_name;
		if (file_exists($controller)) {
			require_once $controller;
			return false;
		}
	}

	$model = $conf->models . $file_name;
	if (file_exists($model)) {
		require_once $model;
		return false;
	}
	
	return false;
}
