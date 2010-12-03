<?php

class Item extends HeliumDBRecord {
	const code_length = 5;
	public $nice_price;
	public $code;
	public $description;
	public $price;
	public $supplier_id;
	
	public function __build() {
		$this->belongs_to('supplier');
		$this->belongs_to('category');
		$this->belongs_to('purchase');
	}

	public static function find() {
		// self::collect_garbage();
		$args = func_get_args();
		return parent::__find(__CLASS__, $args);
	}
	
	public function __parse() {
		$this->nice_price = $this->formatted_price();
	}

	private static function collect_garbage() {
		$db = Helium::db();
		$query = "DELETE FROM `items` WHERE `description`='' AND `price`=''";
		$db->query($query);
	}
	
	public function buy($purchase) {
		if (!($purchase instanceof Purchase))
			return false;
		if ($this->quantity == 0)
			return false;
			
		$this->link($purchase);
		$this->quantity = 0;
		$this->save();
	}
	
	public static function format_price($price) {
		if (strlen($price) < 18)
			return 'Rp' . number_format($price, 0, ',', '.');
		else
			return 'Rp' . $price;
	}

	public function formatted_price() {
		$this->nice_price = self::format_price($this->price);
		return $this->nice_price;
	}

	public function resolve_code($supplier_code) {
		if (!$supplier_code)
			return false;
		if ($this->code)
			return $this->code;

		$db = Helium::db();

		$supplier = Supplier::find_by_code($supplier_code);
		if (!$supplier) {
			$supplier = new Supplier;
			$supplier->code = $supplier_code;
			$supplier->save();
		}

		$sup_e = $db->escape($supplier_code);
		$where = "WHERE `code` LIKE '$sup_e%' ORDER BY `code` DESC";
		$items = self::find($where);
		if ($items) {
			foreach ($items as $item) {
				// clean up orphans
				if (!$item->description && !$item->price) {
					$item->destroy();
					continue;
				}
				if (strlen($item->code) == self::code_length) {
					$last_code = $item->code;
					break;
				}
				else
					continue;
			}
		}
		if (!$last_code)
			$last_code = $supplier_code . "00";

		$digits = substr($last_code, strlen($supplier_code));
		$digits_length = strlen($digits);
		$digits = intval($digits);

		$still = true;
		while ($still) {
			$digits += 1;
			$new_digits = (string) intval($digits);
			$new_digits = str_pad($new_digits, $digits_length, '0', STR_PAD_LEFT);
			$new_code = $supplier_code . $new_digits;
			$test = self::find(array('code' => $new_code));
			if (!$test)
				$still = false;
		}

		return $new_code;
	}
}