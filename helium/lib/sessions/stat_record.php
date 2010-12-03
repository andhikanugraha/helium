<?php

class StatRecord extends HeliumDBRecord {
	public $params = array();

	public static function find() {
		return parent::__find(__CLASS__, func_get_args());
	}
}


