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
		$this->field_types = get_field_types($this->prototype);

		if (!$this->items)
			$this->items = array();

		if ($did = $this->params['deleted'])
			$this->last_delete = $did;
		if ($did = $this->params['added'])
			$this->last_add = $did;
		if ($did = $this->params['failed_to_delete'])
			$this->failed_to_delete = $did;

		$this->has_many = $sample->__has_many;
		if (is_array($this->has_many)) {
			foreach ($this->has_many as $key => $foreign_id) {
				$what = Inflector::singularize($key);
				$value = array('fields' => get_fields($what),
						'field_types' => get_field_types($what),
						'foreign_id' => $foreign_id);
				$value['matches'] = array();
				foreach ($this->items as $item)
					$value['matches'][$item->id] = find_records($what, array($foreign_id => $item->id));
				$this->has_many[$key] = $value;
			}
		}

		$habtm = $sample->__has_and_belongs_to_many;
		$this->has_and_belongs_to_many = array();
		foreach ($habtm as $key) {
			$what = Inflector::singularize($key);
			$value = array('fields' => get_fields($what),
					'field_types' => get_field_types($what),
					'matches' => array());
			foreach ($this->items as $item) {
				$thing = find_first_record($this->prototype, $item->id);
			 	$value['matches'][$item->id] = $thing->$key;
			}
			$this->has_and_belongs_to_many[$key] = $value;
		}
	}
	
// 	public function build() {
// 		$table_name = Inflector::pluralize($this->prototype);
// 		$class_name = Inflector::classify($this->prototype);
// 
// 		$properties = get_class_vars($class_name);
// 		$relations = array('__has_one', '__belongs_to','__has_many',  '__has_and_belongs_to_many');
// 		$types = array();
// 		$defaults = 
// 		foreach ($properties as $property) {
// 			if (in_array($property->name, $relations))
// 				continue;
// 		}
// 
// 		$query = <<<EOF
// CREATE TABLE `$table_name` (
// `id` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
// EOF;
// 		$query .= <<<EOF
// `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
// `updated_at` TIMESTAMP NOT NULL
// )
// EOF;
// 	}

	public function destroy() {
		$id = intval($this->params['id']);
		$sample = find_first_record($this->prototype, $id);
		if (is_object($sample) && $sample->destroy()) {
			$this->redirect_action('index', array('deleted' => $id));
			return;
		}

		$this->redirect_action('index', array('failed_to_delete' => $id));
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