<?php

// TO BE REWRITTEN
function __autoload($class_name) {
	$model_name = Inflector::underscore($class_name);

	Helium::load_app_file('model', $model_name);

	return;
}