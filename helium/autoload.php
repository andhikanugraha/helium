<?php

function __autoload($class_name) {
	$model_name = Inflector::underscore($class_name);

	Helium::load_app_file('models', $model_name);

	return;
}