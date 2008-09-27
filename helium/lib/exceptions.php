<?php

// Helium Framework: Helium
// Exception handler

class HeliumException extends Exception {
	const no_route = 0;
	const no_model = 1;
	const no_view = 2;
	const no_controller = 3;
	const no_action = 4;
	const no_class = 5;
	const smarty = 7;
	const failed_to_redirect = 8;
	const plugin_not_found = 9;
	const file_not_found = 10;
	const php_error = 256;

	public $code = 0;
	public $message = 'Unknown error';
	public $http_status = 500;
	public $file;
	public $line;
	public $request;
	public $controller;
	public $action;
	public $params = array();

	public static $net = array();

	public function __construct($code) {
		global $he;

		$core = $he;
		$this->controller = $core->controller;
		$this->action = $core->action;
		$this->params = $core->params;
		$this->request = $core->request;

		$this->code = $code;

		if ($this->request && $first_slash = strpos($this->request, '/', 1)) {
			$first_dir = substr($this->request, 1, $first_slash - 1);
			$try = SITE_PATH . $first_dir;
			if ($first_dir && file_exists($try))
				$this->just_do_404($first_dir);
		}

		$args = func_get_args();
		array_shift($args);

		// figure out the error message
		// $message willg be sprintf()ized.
		// %1 will be the request
		// %2 will be the controller
		// %3 will be the action
		if (is_int($this->code)) {
			switch ($this->code) {
			case self::no_route:
				$message = 'The request <kbd>%s</kbd> could not be routed to any controller.';
				break;
			// seems that this is deprecated, since __autoload doesn't throw anything
			case self::no_model:
				list($table) = $args;
				$table = Inflector::underscore($table);
			 	$message = "The database table <kbd>$table</kbd> does not have a model associated to it.";
			 	break;
			case self::no_controller:
				$message = 'The controller <kbd>%2$s</kbd> does not exist.';
				$status = 404;
				break;
			case self::no_action:
				$message = 'The request <kbd>%s</kbd> could not be routed to any action.';
				break;
			case self::no_view:
				$message = 'The action <kbd>%2$s::%3$s</kbd> does not have a view associated to it.';
				$status = 404;
				break;
			case self::no_class:
				list($class) = $args;
				$message = "The class <kbd>$class</kbd> does not exist.";
				break;
			case self::smarty:
				list($message) = $args;
				$message = '<strong>Smarty error:</strong> ' . $message;
				break;
			case self::failed_to_redirect:
				list($uri) = $args;
				$message = "Redirection to <kbd>$uri</kbd> failed.";
				break;
			case self::plugin_not_found:
				list($plugin, $plugin_dir) = $args;
				$message = "Plugin <kbd>$plugin</kbd> could not be found under <kbd>$plugin_dir</kbd>.";
				break;
			case self::php_error:
				list($php_error_code, $message, $this->file, $this->line) = $args;
				$php_error_code_map = array(E_ERROR => 'Fatal error',
											E_WARNING => 'Warning',
											E_PARSE => 'Parse error',
											E_NOTICE => 'Warning',
											E_USER_WARNING => 'Warning');
				$error_type = $php_error_code_map[$php_error_code];
				if ($error_type)
					$message = "<strong>$error_type:</strong> $message";
				else
					$message = "<strong>Error code $php_error_code</strong> $message";
				break;
			case self::file_not_found:
				$this->http_status = 404;
				$message = "Static file <kbd>%s</kbd> was not found.";
				break;
			default:
				$message = 'Unknown error.';
			}
		}
		elseif (is_string($this->code)) {
			$message = $this->code;
		}

		$message = sprintf($message, $this->request, $this->controller, $this->action, $this->params['id']);
		$this->log_message($message);

		$filename = str_replace('\\', '/', $this->file);
		$filename = str_replace(HE_PATH, '<kbd class="variable">HE_PATH</kbd>', $filename);
		$filename = str_replace(SITE_PATH, '<kbd class="variable">SITE_PATH</kbd>', $filename);
		$this->formatted_filename = $filename;

		$this->trace = $this->getTrace();
		$this->trace_string = $this->getTraceAsString();

		$clean_trace = array();
		foreach ($this->trace as $key => $line) {
			if (!$line)
				continue;

			$dummy = array();
			if (is_array($line['args'])) {
				foreach ($line['args'] as $arg) {
					if (is_string($arg)) {
						if (strlen($arg) > 30)
							$arg = substr($arg, 0, 27) . '...';
						$dummy[] = "<code class=\"string\">\"$arg\"</code>";
					}
					else {
						if (is_object($arg))
							$arg = get_class($arg);
						$dummy[] = "<code class=\"value\">$arg</code>";
					}
				}
			}
			$line['args'] = implode('<br/>', $dummy);

			$line['file'] = str_replace('\\', '/', $line['file']);
			$line['file'] = str_replace(HE_PATH, '<kbd class="variable">HE_PATH</kbd>', $line['file']);
			$line['file'] = str_replace(SITE_PATH, '<kbd class="variable">SITE_PATH</kbd>', $line['file']);

			$clean_trace[$key] = $line;
		}

		$this->formatted_trace = $clean_trace;
	}

	private function log_message($message) {
		self::$net[] = $message;
		$this->message = $message;
	}

	public function output() {
		global $conf;

		$this->send_http_status();
		$messages = self::$net;
		require_once HE_PATH . '/lib/views/exception.php';
		exit;
	}

	private function send_http_status($status = null) {
		$status = $status ? $status : $this->http_status;

		if (class_exists('HeliumHTTPResponse')) {
			global $response;
			$response->set_response_code($status);
			return;
		}

		$statuses = array(401 => 'Unauthorized',
						  403 => 'Forbidden',
						  404 => 'Not Found',
						  405 => 'Method Not Allowed',
						  500 => 'Internal Server Error');
		$message = $statuses[$status];

		if (!headers_sent())
			@header("HTTP/1.1 $status $message");
	}

	private function just_do_404($dir) {
		switch ($dir) {
		case Helium::conf('stylesheets_dir'):
			$this->send_http_status(404);
			@header('Content-type: text/css');
			echo "/* file not found */";
			exit;
		case Helium::conf('javascripts_dir'):
			$this->send_http_status(404);
			@header('Content-type: application/x-javascript');
			echo "// file not found";
			exit;
		default:
			$this->code = self::file_not_found;
		}
	}
}

function helium_error_handler($code, $message, $file, $line) {
	$e = new HeliumException(HeliumException::php_error, $code, $message, $file, $line);
	if (class_exists('HeliumHTTPResponse')) {
		global $response;
		if (!$response)
			$response = new HeliumHTTPResponse;
		
		$response->set_content_type('text/html');
	}
	else {
		if (!headers_sent())
			header('Content-type: text/html');
	}
	$e->output();
}
set_error_handler('helium_error_handler', E_ALL ^ E_NOTICE);