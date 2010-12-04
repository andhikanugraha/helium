<?php

// HeliumRecord
// Helium's implementation of the Active Record model.

abstract class HeliumRecord {
	private $_exists;
	private $_table;
	private $_columns = array();
	private $_column_types = array();
	private $_singular_relations = array();
	private $_plural_relations = array();
	private $_many_to_many_relations = array();
	private $_built = false;

	public function __construct() {
		$table = get_class($this);
		$table = Inflector::tableize($table);
		$this->_table = $table;

		foreach ($this->_columns() as $column) {
			if (!isset($this->$column))
				$this->$column = null;
		}

		$this->relations();
	}

	/* blank methods
	   functions that can be redefined by child functions */

	// relation definitions (has_many, etc) go here
	public function relations() {}

	// called when the record is fetched from the database
	public function build() {}


	/* finding functions
	   functions for and related to fetching records from the DB */

	// find() now uses HeliumRecordSet.
	final public static function find($conditions = null) {
		if (is_numeric($conditions)) { // we're looking for a single record with an ID.
			$multiple = self::find(array('id' => $conditions));
			return $multiple->single;
		}

		$class_name = get_called_class();
		$set = new HeliumRecordSet($class_name);

		if ($conditions === null)
			$set->conditions_string = '1';
		elseif (is_array($conditions))
			$set->conditions_array = $conditions;
		elseif (is_string($conditions))
			$set->conditions_string = trim($conditions);

		return $set;
	}

	// invoking the object as a function fills it with data
	final public function __invoke(StdClass $result) {
		foreach ($this->_columns as $column) {
			$this->$column = $result->$column;
		}

		$this->_exists = true;
		$this->_convert_columns();

		$this->build();

		return $this;
	}

	// relational functions

	final protected function has_one($model_name, $local_key = null) {
		if (!$local_key)
			$local_key = $model_name . '_id';

		$this->_singular_relations[$model_name] = $local_key;
	}

	final protected function belongs_to($model_name, $local_key = null) {
		$this->has_one($model_name, $local_key);
	}

	final protected function has_many($model_name, $foreign_key = null) {
		if (!$foreign_key)
			$foreign_key = Inflector::singularize($this->_table) . '_id';

		$this->_plural_relations[$model_name] = $foreign_key;
	}

	final protected function belongs_to_many($class_name, $foreign_key = null) {
		$this->has_many($class_name, $foreign_key);
	}

	final protected function has_and_belongs_to_many($foreign_table) {
		$sort = array($foreign_table, $this->_table);
		sort($sort);
		$join_table = implode('_', $sort);
		$foreign_key = $foreign_single . 'id';

		$this->_many_to_many_relations[$foreign_table] = $join_table;
	}

	// internal mapping functions for relations - REWRITE

	final private function _map_singular_relation($model_name, $local_key) {
		if ($this->$local_key !== null) {
			$class_name = Inflector::camelize($model_name);
			$return = $class_name::find($this->$local_key);
		}
		else
			$return = null;

		$this->$model_name = $return;

		return $return;
	}

	final private function _map_plural_relation($model_name, $foreign_key) {
		$id = $this->id;
		if ($id !== null) {
			$class_name = Inflector::camelize($model_name);
			$return = $class_name::find(array($foreign_key => $id));
		}

		$this->$class_name = $return;

		return $return;
	}

	final protected function _map_many_to_many_relation($foreign_table, $join_table) {
		$db = Helium::db();

		$foreign_single = Inflector::singularize($foreign_table);
		$local_single = Inflector::singularize($this->_table);
		$foreign_key = $foreign_single . '_id';
		$local_key = $local_single . '_id';

		$query = "SELECT `$foreign_key` FROM `$join_table` WHERE `$local_key`='{$this->id}'";
		$foreign_ids = $db->get_col($query);

		if (!$foreign_ids || !is_array($foreign_ids))
			return false;

		$foreign_class_name = Inflector::camelize($foreign_single);
		$foreigners = $foreign_class_name::find();
		foreach ($foreign_ids as $foreign_id)
			$foreigners->add_ID($foreign_id);

		$this->$foreign_table = $foreigners;

		return true;
	}

	// overloading for relation support

	final public function __get($name) {
		if ($local_key = $this->_singular_relations[$name]) {
			$this->_map_singular_relation($name, $local_key);
			return $this->$name;
		}
		else if ($foreign_key = $this->_plural_relations[$name]) {
			$this->_map_plural_relation($name, $foreign_key);
			return $this->$name;
		}
		else if ($join_table = $this->_many_to_many_relations[$name]) {
			$this->_map_many_to_many_relation($name, $join_table);
			return $this->$name;
		}
		else
			return null;
	}

	final public function __isset($name) {
		return ($this->_singular_relations[$name] || 
				$this->_plural_relations[$name] ||
				$this->_many_to_many_relations[$name]);
	}

	// manipulation functions
	
	
	public function save() {
		$db = Helium::db();

		$table = $this->_table;

		if ($this->_exists) {
			$query = array();
			foreach ($this->_db_values() as $field => $value) {
				$query[] = "`$field`='$value'";
			}
			if (in_array('updated_at', $this->_columns()))
				$query[] = "`updated_at`=NOW()";

			$query = implode(', ', $query);

			$id = $this->id;
			$query = "UPDATE $table SET $query WHERE `id`='$id'";

			$query = $db->query($query);

			$this->_refresh_relations();
		}
		else {
			$fields = $values = array();
			foreach ($this->_columns() as $field) {
				if (!$this->$field || $field == 'created_at' || $field == 'updated_at')
					continue;

				$fields[] = "`$field`";
				$values[] = "'" . $db->escape($this->$field) . "'";
			}

			if (in_array('created_at', $this->_columns())) {
				$fields[] = "`created_at`";
				$values[] = "NOW()";
			}

			$fields = implode(', ', $fields);
			$values = implode(', ', $values);

			$query = "INSERT INTO $table ($fields) VALUES ($values)";

			$query = $db->query($query);

			$this->id = $db->insert_id;
		}

		if (!$query)
			return false;

		$this->_exists = true;

		return true;
	}

	// to extend in child classes, call parent::destroy();
	public function destroy() {
		$db = Helium::db();

		$table = $this->_table;
		$id = $this->id;

		$query = $db->query("DELETE FROM `$table` WHERE `id`='$id'");

		if ($query) {
			$unset = $this->_columns();
			foreach ($unset as $field)
				$this->$field = null;
		}
	}

	// under-the-hood database functions

	final public function _columns() {
		if (!$this->_columns) {
			$db = Helium::db();
			$table = $this->_table;
			$query = $db->get_results("SHOW COLUMNS FROM `$table`");

			$columns = array();
			foreach ($query as $row) {
				$field = $row->Field;
				$type = $row->Type;

				$columns[] = $field;
				if ($type == 'tinyint(1)') // boolean
					$type = 'bool';
				elseif (($pos = strpos($type, '(')) > 0)
					$type = substr($type, 0, $pos);

				$this->_column_types[$field] = $type;
			}

			$this->_columns = $columns;
		}

		return $this->_columns;
	}

	private function _convert_columns() {
		$this->columns(); // to fetch the column types if not yet fetched

		foreach ($this->_column_types as $field => $type) {
			$value = $this->$field;

			switch ($type) {
			case 'bool':
				$value = $value ? true : false;
				break;
			case 'int':
			case 'tinyint':
			case 'bigint':
				$value = Helium::numval($value);
				break;
			case 'datetime':
			case 'date':
			case 'timestamp':
				$value = strtotime($value);
				break;
			case 'varchar':
			case 'char':
			case 'text':
			default:
				$value = strval($value); // actually, this isn't necessary.
			}

			$this->$field = $value;
		}
	}

	private function _db_values() {
		$db = Helium::db();
		$fields = array();

		foreach ($this->__column_types as $field => $type) {
			$value = $this->$field;

			switch ($type) {
			case 'bool':
				$value = $value ? 1 : 0;
				break;
			case 'int':
			case 'tinyint':
			case 'bigint':
				$value = Helium::numval($value);
				break;
			case 'datetime':
			case 'date':
			case 'timestamp':
				$value = $db->timetostr($value, $type);
				break;
			case 'varchar':
			case 'char':
			case 'text':
			default:
				if (is_array($value) || is_object($value))
					$value = serialize($value);

				$value = $db->escape($value);
			}

			$fields[$field] = (string) $value;
		}

		return $fields;
	}

	// other functions

	public function merge($source) {
		if (is_object($source))
			$source = get_object_vars($source);
		if (!is_array($source))
			return false;

		foreach ($this->_columns() as $column) {
			if ($source[$column] != $this->$column)
				$this->$column = $source[$column];
		}

		return true;
	}

	public function link($class) {
		$class_name = get_class($class);
		$single_name = Inflector::underscore($class_name);
		$field = $this->_singular_relations[$single_name];

		if (!$field)
			return false;

		if ($this->$field != $class->id)
			$this->$field = $class->id;

		if (get_class($this->$single_name) == $class_name && $this->$single_name->id == $class->id)
			$this->$single_name->merge($class);
		else
			$this->$single_name = $class;

		return true;
	}
}


