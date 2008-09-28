<?php

// Helium framework
// function __autoload

function __autoload($class_name) {
	global $conf;

	$presets = array('Helium_Controller' => HE_PATH . '/lib/controller.php',
					 'Helium_ActiveRecord' => HE_PATH . '/lib/active_record.php',
					 'Smarty' => $conf->paths['smarty'] . '/smarty.class.php',
					 'SmartyOnHelium' => HE_PATH . '/lib/smarty.php',);

	if ($presets[$class_name] && file_exists($presets[$class_name])) {
		require_once $presets[$class_name];
		return true;
	}

	$file_name = '/';
	$file_name .= Inflector::underscore($class_name);
	$file_name .= '.php';

	if (!$conf)
		$conf = new Helium_Configuration;

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
