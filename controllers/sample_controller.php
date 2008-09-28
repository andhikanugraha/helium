<?php

// Yes, this is enough.
class SampleController extends Helium_Controller {
	public $prototype = 'user';
	public $items;
	public $fields;

	protected function __build() {
		$user = new User;
		$this->user = $user;
	}

	public function index() {
		$this->items = find_records($this->prototype);
		$this->fields = get_fields($this->prototype);
	}
	
	public function add() {
		$this->user->type = 1;
		$this->user->display_name = 'John Doe';
		$try = $this->user->save();
		$this->__view = 'index';
		$this->dump = "User #{$this->user->id} created!\n";
		$this->dump .= print_r($this->user, true);
	}
}