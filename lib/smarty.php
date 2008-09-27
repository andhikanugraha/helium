<?php

// Helium Framework: Helium
// Smarty Template Engine driver

class SmartyOnHelium extends Smarty {
	private $__layout = '';
	private $__body = '';

    public function __construct() {
        parent::Smarty();

		$this->set_variables();

		$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (strpos($method, 'smarty_function_') === 0)
				$this->register_function(substr($method, 16), array($this, $method));
			if (strpos($method, 'smarty_block_') === 0)
				$this->register_block(substr($method, 13), array($this, $method));
		}
    }

	private function set_variables() {
		static $done;
		if ($done)
			return;

		global $conf;

		$lib = HE_PATH . '/lib';
        $this->template_dir = $conf->paths['views'];
        $this->compile_dir = $conf->paths['smarty_compile'];
        $this->cache_dir = $conf->paths['smarty_cache'];
        $this->config_dir = $conf->paths['smarty'] . '/configs';

		$done = true;
	}
	
	public function determine_incompatibility() {
		$this->set_variables();
		
		if (!is_dir($this->compile_dir))
			return HeliumException::smarty_compile_cache_nonexistent;
		if (!is_dir($this->cache_dir))
			return HeliumException::smarty_cache_nonexistent;
		
		return false;
	}

    public function yell() {
		if ($code = $this->determine_incompatibility())
			throw new HeliumException($code);

		global $conf;

		if (!$this->__body)
			throw new HeliumException(HeliumException::no_view);

		if ($this->__layout)
			$this->display($this->__layout);
		else
			$this->display($this->__body);
			
		$this->production = $GLOBALS['conf']->production;
	}

	public function set_body($template, $append_extension = true) {
		$this->__body = $template;
		if ($append_extension)
			$this->__body .= '.tpl';
	}
	
	public function set_layout($template, $append_extension = true) {
		$this->__layout = $template;
		if ($append_extension)
			$this->__layout .= '.tpl';
	}

	// override Smarty's error handler

	public function trigger_error($message) {
		throw new HeliumException(HeliumException::smarty, $message);
	}
	
	// Smarty plugins

	public function import($object) {
		global $router, $controller;

		$controller_class = $router->controller_class;
		if ($object instanceof $controller_class)
			$this->assign('controller', $controller);
		elseif ($controller instanceof $controller_class)
			$this->assign('controller', $object);

		$this->assign('router', get_object_vars($router));
		$this->assign('helium_version', HE_VERSION);

		foreach ($object as $var => $value) {
			$this->assign($var, $value);
		}
	}

    public function smarty_function_body() {
        return $this->fetch($this->__body);
    }

	public function smarty_function_header() {
		if ($this->$production)
			return @$this->fetch('shared/header.tpl');
		else
			return $this->fetch('shared/header.tpl');
	}

	public function smarty_function_footer() {
		if ($this->$production)
			return @$this->fetch('shared/footer.tpl');
		else
			return $this->fetch('shared/footer.tpl');
	}

	public function smarty_function_stylesheet_link_tag($params) {
		global $conf;

		$filename = $params['name'] . '.css';
		$media = $params['media'];
		$dir = $conf->paths['stylesheets'];
		$path = $dir . '/' . $filename;
		$tag = sprintf('<link rel="stylesheet" type="text/css" media="%2$s" href="%1$s" />', $path, $media);
		return $tag;
	}

	public function smarty_block_link_to($params, $content) {
		global $router;

		$controller = $params['controller'];
		$action = $params['action'];

		unset($params['controller']);
		unset($params['action']);

		$path = $router->resolve_path($controller, $action, $params);

		$tag = '<a href="' . $path . '">' . $content . '</a>';
		return $tag;
	}
}