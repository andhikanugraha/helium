<?php

// TO BE REWRITTEN
function __autoload($class_name) {
	$maybe_a_controller = false;
	$file_name = Inflector::underscore($class_name);

	$model = Helium::conf('models') . '/' . $file_name . '.php';
	$code = $maybe_a_controller ? HeliumException::no_controller : HeliumException::no_model;
	if (!file_exists($model))
		throw new HeliumException($code, $class_name);
	elseif (file_exists($model))
		require_once($model);

	return;
}
