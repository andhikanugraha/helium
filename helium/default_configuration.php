<?php

// Helium
// Default configuration

class HeliumDefaults {

	// absolute URL to the site
	public $base_uri = ''; // optional, but required for canonical URIs.

    // database info


	// ADVANCED CONFIGURATION

	public $core_path;
	public $app_path;
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

		$core_path = realpath(dirname(__FILE__) . '/../'); // the /helium directory path
		$core_path = str_replace('\\', '/', $core_path) . '/';
		$this->core_path = $core_path;

		$app_path = realpath($this->app_path);
        if (!$this->app_path || !file_exists($app_path))
			$app_path = $core_path . 'app';
		$app_path = rtrim($app_path, '\/');
		$app_path = str_replace('\\', '/', $app_path);
        $this->app_path = $app_path;

       if (!$this->models || !file_exists($models))
			$models = $core_path . 'models';
		$models = rtrim($models, '\/');
		$models = str_replace('\\', '/', $models);
        $this->models = $models;

		if (!$this->base_uri)
			$this->base_uri = 'http://' . $_SERVER['HTTP_HOST'];

        if (!$this->db_name && $this->db_user)
            $this->db_name = $this->db_user;
    }
}
