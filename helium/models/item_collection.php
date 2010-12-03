<?php

abstract class ItemCollection extends HeliumDBRecord {

	public function __parse() {
		$this->calculate_counts();
	}

	public function calculate_counts() {
		if ($this->id === null)
			return;

		$db = Helium::db();
		$model = Inflector::underscore(get_class($this));
		$foreign_key = $model . '_id';
		$query = "SELECT
		COUNT(*) `item_count`,
		SUM(1 - quantity) `sold_item_count`,
		SUM(quantity) `available_item_count`,
		SUM(price) `total_price`,
		SUM(price - price * quantity) `total_sold_price`,
		SUM(price * quantity) `total_available_price`
		FROM `items` WHERE `{$foreign_key}`='{$this->id}'";
		$row = $db->get_row($query);
		foreach ($row as $key => $value) {
			if (strpos($key, 'price') > -1) {
				$nice_key = 'nice_' . $key;
				$this->$nice_key = Item::format_price($value);
			}
			$this->$key = $value;
		}
	}

}

?>