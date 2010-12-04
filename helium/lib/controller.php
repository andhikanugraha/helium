<?php

// HeliumController
// the C in Helium's MVC.

// here's how things are going to work:
// every *public*, non-magic method in a Controller represents an Action.
// when the user makes a request, Helium will call the controller class as a function (__invoke).
// __invoke will do two things:
// 1. call the method that corresponds to the function
// 2. include() the viewport, thus maintaining object scope.

abstract class HeliumController {
	
	public $load_view = true; // should the view be loaded?
	
	public $action;
	public $params;

	public function __construct() {
		$core = Helium::core();
		$this->action = $core->action;
		$this->params = $core->params;

		// add module overloading here
	}

	public function __invoke() {
		$controller_class_name = get_class($this); // since we're not calling statically, this is enough
		$controller_underscore_name = Inflector::underscore($this);
		$controller = substr($controller_underscore_name, 0, strlen($controller_underscore_name) - 11); // cut off the _controller part.
		
		$action = $this->action;

		/* validation */

		// check if action exists or not
		if (!function_exists(array($this, $action)))
			throw new HeliumException(HeliumException::no_action);

		// check if viewport exists or not
		// yes, the pattern for naming views is defined here.
		$view_path = Helium::conf('views_path') . '/' . $action . '.php';
		if ($this->load_view && !file_exists($view_path))
			throw new HeliumException(HeliumException::no_view);

		/* execution */
		// the action and view exists. everything is safe.

		$this->$action();

		if ($this->load_view)
			include_once $view_path; // include is enough, we don't want fatal errors here.
	}

	// helper functions
	
	// do a HTTP redirect
	// TODO: implement controller-and-action backmapping.
	protected function redirect($destination) {
		$base_uri = Helium::conf('base_uri');

		if (strpos($destination, '://') < 0) // relative URL
			$destination = $base_uri . $destination;
			
		if (!headers_sent()) {
			@header("Location: $target");
			exit;
		}
		else
			throw new HeliumException(HeliumException::failed_to_redirect, $target);
	}
	
	// TODO: something that corresponds to the build_path thing.
	protected function uri() {}
}