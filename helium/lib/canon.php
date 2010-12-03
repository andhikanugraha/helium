<?php

// HeliumCanon class
// implementation of canonical URIs.

if (class_exists('HeliumCanon'))
	return;

// not necessarily a singleton...
final class HeliumCanon {
	public $base_uri;
	
	public function __construct() {
		static $canonized = false;
		if ($canonized)
			return;

		if (Helium::conf('base_uri'))
			$this->base_uri = Helium::conf('base_uri');
		else
			$this->base_uri = $this->resolve_base_uri();

		$conf = Helium::conf();

		$conf->base_uri = $this->base_uri = rtrim($this->base_uri, '/');

		$url = parse_url($conf->base_uri);
		$conf->base_path = rtrim($url['path'], '/');
		if (!$conf->domain)
			$conf->domain = $url['host'];
		if ($conf->protocol != $url['scheme'])
			$conf->protocol = $url['scheme'];

		$canonized = true;
	}

	public function resolve_base_uri() {
		$conf = Helium::conf();

		$protocol = strtolower($conf->protocol);
		$template = $protocol . '://%s';

		$domain = $conf->domain;
		if (!$domain) {
			$domain = $_SERVER['HTTP_HOST'];
			$conf->domain = $domain;
		}

		$this->parse_request();

		$essential = dirname($_SERVER['PHP_SELF']);
		$essential = str_replace('\\', '/', $essential);
		$essential = $essential;

		return sprintf($template, $domain . $essential);
	}

	public function enforce() {
		$core = Helium::core();
		$map = $core->map;
		$actual_request = $core->map->raw_request;
		$proper_request = $map->build_path($core->controller, $core->action, $core->params);

		if ($actual_request != $proper_request) {
			$core->redirect($core->controller, $core->action, $core->params);
		}
	}
}