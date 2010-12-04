<?php

/* @model: Session
 * -- TEXT params 
 * -- INT user_id
 * -- INT stat_record_id
 * @has_one user
 * @has_one stat_record
 */


class Session extends HeliumDBRecord {
	public $data = array();

	public function __build() {
		$this->has_one('user');
	}

	public function __parse() {
		$this->data = unserialize($this->data);
	}

	public static function find() {
		$args = func_get_args();
		return parent::__find(__CLASS__, $args);
	}

	public function save() {
		$data = $this->data;
		$this->data = serialize($data);
		$this->__save();
		$this->data = (array) $data;
	}
}