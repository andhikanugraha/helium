<?php

// Helium framework
// function __autoload

function __autoload($class_name) {
	$presets = array('HeliumController' => HE_PATH . '/lib/controller.php');
	if ($presets[$class_name] && file_exists($presets[$class_name])) {
		require_once $presets[$class_name];
		return true;
	}

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
