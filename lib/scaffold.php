<?php

// Helium framework
// class Helium_Scaffold
// SCAFFOLDING!

class Helium_Scaffold extends Helium_Controller {
	const route = '[controller]//[action]/[id]';
	public $prototype = '';
	public $items;
	public $fields;

	protected function __build() {
		$this->__view_path = HE_PATH . '/lib/views/scaffold/index';

		if (!$this->prototype) {
			global $controller_name;
			$this->prototype = $controller_name;
		}
	}

	public function index() {
		$class = Inflector::classify($this->prototype);
		$sample = new $class;
		$this->items = find_records($this->prototype);
		$this->fields = get_fields($this->prototype);
		$this->has_many = $sample->__has_many;
		foreach ($this->has_many as $key => $foreign_id) {
			$what = Inflector::singularize($key);
			$value = array('fields' => get_fields($what),
				'foreign_id' => $foreign_id);
			$value['matches'] = array();
			foreach ($this->items as $item)
				$value['matches'][$item->id] = find_records($what, array($foreign_id => $item->id));
			$this->has_many[$key] = $value;
		}
	}

	// public function add() {
	// 	$this->user->type = 1;
	// 	$this->user->display_name = 'John Doe';
	// 	$try = $this->user->save();
	// 	$this->__view = 'index';
	// 	$this->dump = "User #{$this->user->id} created!\n";
	// 	$this->dump .= print_r($this->user, true);
	// }
}