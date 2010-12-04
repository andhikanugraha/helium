<?php

// Helium
// Default configuration

class HeliumDefaults {

	// absolute URL to the site
	public $base_uri = ''; // optional if installed on a root directory

    // database info


	// ADVANCED CONFIGURATION

	public $core_path = HELIUM_PATH;
	public $app_path = HELIUM_APP_PATH;
	public $models;

    public $db_user = '';
    public $db_pass = '';
    public $db_name = '';			// optional; defaults to $db_user
    public $db_host = 'localhost';

	public $protocol = 'http';	// set to 'https' to enable SSL

    public function __construct() {
        static $done = false;

        if ($done)
            return;

		if (!$this->base_uri)
			$this->base_uri = $this->protocol . '://' . $_SERVER['HTTP_HOST'];

        if (!$this->db_name && $this->db_user)
            $this->db_name = $this->db_user;
    }
}
