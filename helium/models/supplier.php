<?php

class Supplier extends ItemCollection {

	public function __build() {
		$this->has_many('items');
	}

	public static function find() {
		$args = func_get_args();
		return parent::__straight_find(__CLASS__, $args);
	}

	public static function find_by_code($code) {
		if (strlen($code) != 3)
			return array();

		$suppliers = self::find(array('code' => $code));
		if ($suppliers)
		        return $suppliers[0];
		else
		        return array();
	}
	
	public function save() {
		if (!$this->code)
			return false;
		else
			return parent::save();
	}

	public function destroy() {
		if ($this->items)
			return false;
		else
			parent::destroy();
	}
}