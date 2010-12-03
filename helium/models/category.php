<?php

class Category extends ItemCollection {
	public $name = 'Uncategorized';

	public function __build() {
		$this->has_many('items');
	}

	public static function find() {
		$args = func_get_args();
		return parent::__straight_find(__CLASS__, $args);
	}

	public function __parse() {
		if (!$this->name)
			$this->name = 'Uncategorized';
		parent::__parse();
	}
	
	public function destroy() {
		$id = $this->id;
		parent::destroy();

		$new_id = Category::find(null, true);
		$new_id = $new_id->id;
		$items = Item::find(array('category_id' => $id));
		foreach ($items as $item) {
			$item->category_id = $new_id;
			$item->save();
		}
	}
}