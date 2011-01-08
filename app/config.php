<?php

// HeliumConfiguration
// Edit this class to set global configuration variables for your application

class HeliumConfiguration extends HeliumDefaults {

	/* Application configuration */
	public $app_name = 'helium';	// name of application
	public $production = false;		// set to true to disable debugging
	// public $enable_reactor = false;	// true to enable Reactor

	/* MySQL Database configuration – optional if database is not being used */

	public $db_user = 'username';	// username
	public $db_pass = 'password';	// password
	public $db_name = 'database';	// database name – optional; defaults to $db_user
	public $db_host = 'localhost';	// database server – optional; defaults to localhost

}