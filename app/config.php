<?php

// Configuration

class HeliumConfiguration extends HeliumDefaults {
	// absolute URL to the site
	// public $base_uri = 'http://localhost'; // optional, but required for canonical URIs.

    // database info
    public $db_user = 'garasi';
    public $db_pass = '2010';
    public $db_name = 'garasi';			// optional; defaults to $db_user
    public $db_host = 'localhost';		// optional; defaults to localhost

	public $sync_remote_gateway = 'http://jg.thursdaypeople.com/sync/gateway';
}

Helium::$canonize = false;
Helium::$app_id = 'jualangarasi';