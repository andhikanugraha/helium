<?php

class Purchase extends ItemCollection {
	public $name = 'Uncategorized';

	public function __build() {
		$this->has_many('items');
	}

	public static function find() {
		$args = func_get_args();
		return parent::__find(__CLASS__, $args);
	}
}