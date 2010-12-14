<?php

// A helper is a set of logic that is shared between viewports.

abstract class HeliumHelper extends HeliumControllerSupport {

	public $controller_object;

	// initialize and perhaps do something with the controller
	// for example, new methods can be defined by using anonymous functions
	public class init(HeliumController $controller_object) {}
	
}