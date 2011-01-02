<?php

function __autoload($class_name) {
	return Helium::load_class_file($class_name);
}