<?php

// Configuration

class HeliumConfiguration extends HeliumDefaults {
	// absolute URL to the site
	// public $base_uri = 'http://localhost'; // only required if Helium is not installed at root directory

	public $app_name = 'helium';

    // database info
    public $db_user = 'user';
    public $db_pass = 'pass';
    public $db_name = 'name';			// optional; defaults to $db_user
    public $db_host = 'localhost';		// optional; defaults to localhost
}