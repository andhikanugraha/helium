<?php

// A component is a set of logic that is shared between controllers.

abstract class HeliumComponent extends HeliumControllerSupport {

	public $prerequisite_components = array();

	protected $controller_object;

	// initialize and perhaps do something with the controller
	// for example, new methods can be defined by using anonymous functions
	public function init(HeliumController $controller_object) {}

}