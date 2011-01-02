<?php

// Helium
// Default configuration

class HeliumDefaults {

	// absolute URL to the site
	public $base_uri = ''; // optional if installed on a root directory

	// database info

	public $db_user = '';
	public $db_pass = '';
	public $db_name = '';			// optional; defaults to $db_user
	public $db_host = 'localhost';

	// ADVANCED CONFIGURATION

	public $helium_path = HELIUM_PATH;
	public $app_path = HELIUM_APP_PATH;
	public $parent_path = HELIUM_PARENT_PATH;

	public $protocol = 'http';	// set to 'https' to enable SSL

	public function __construct() {
		static $done = false;

		if ($done)
			return;

		if (!$this->base_uri)
			$this->base_uri = $this->protocol . '://' . $_SERVER['HTTP_HOST'];

		if (!$this->db_name && $this->db_user)
			$this->db_name = $this->db_user;

		$app_dirs = array('models', 'views', 'controllers', 'components', 'helpers');
		foreach ($app_dirs as $dir) {
			$conf = $dir . '_path';
			$defined = $this->$conf;
			if (!$defined)
				$this->$conf = $this->app_path . '/' . $dir;
			elseif ($defined[0] == '/')
				$this->$conf = $this->app_path . '/' . $defined;
		}
	}
}
