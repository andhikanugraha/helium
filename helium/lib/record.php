<?php

// HeliumDBRecord
// something like Rails' ActiveRecord::Base.

abstract class HeliumDBRecord {
	private $__exists;
    private $__table;
    private $__columns = array();
	private $__column_types = array();
	private $__singular_relations = array();
	private $__plural_relations = array();
	private $__many_to_many_relations = array();
	private $__built = false;

    public function __construct() {
        $table = get_class($this);
        $table = Inflector::tableize($table);
        $this->__table = $table;

		foreach ($this->__columns() as $column) {
			if (!isset($this->$column))
				$this->$column = null;
		}

		$this->__build();
    }

	// called on find(), parses the SQL data
	public function __found($straight = false) {
		if ($this->__exists)
			return true;

		$this->__exists = true;
		$this->__convert_columns();

		if (!$straight)
			$this->__map_relations();

		$this->__parse();
	}

	// use __build as constructors for descendant classes
	public function __build() {}

	// this is called on find()
	public function __parse() {}
	
	protected static function __find($class, $args) {
		$class = Inflector::underscore($class);
		array_unshift($args, $class);
		return call_user_func_array(array('HeliumDB', 'find'), $args);
	}
	
	protected static function __straight_find($class, $args) {
		$class = Inflector::underscore($class);
		array_unshift($args, $class);
		return call_user_func_array(array('HeliumDB', 'straight_find'), $args);
	}

	// base HeliumDBRecord::find() on HeliumDB::find()
	abstract public static function find();

    public function __columns() {
        if (!$this->__columns) {
            $db = Helium::db();
            $table = $this->__table;
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

				$this->__column_types[$field] = $type;
			}

            $this->__columns = $columns;
        }

        return $this->__columns;
    }

	protected function has_one($class_name, $field = null) {
		if (!$field)
			$field = $class_name . '_id';

		$this->__singular_relations[$class_name] = $field;
	}

	protected function belongs_to($class_name, $field = null) {
		$this->has_one($class_name, $field);
	}

	protected function has_many($class_name, $foreign_key = null) {
		if (!$foreign_key)
			$foreign_key = Inflector::singularize($this->__table) . '_id';

		$this->__plural_relations[$class_name] = $foreign_key;
	}

	protected function belongs_to_many($class_name, $foreign_key = null) {
		$this->has_many($class_name, $foreign_key);
	}
	
	protected function has_and_belongs_to_many($foreign_table) {
		$sort = array($foreign_table, $this->__table);
		sort($sort);
		$join_table = implode('_', $sort);
		$foreign_key = $foreign_single . 'id';

		$this->__many_to_many_relations[$foreign_table] = $join_table;
	}

	private function __map_singular_relation($class_name, $field) {
		if ($this->$field !== null)
			$return = HeliumDB::straight_find($class_name, $this->$field);
		else
			$return = null;

		$this->$class_name = $return;
	}

	private function __map_plural_relation($class_name, $foreign_key) {
		$id = $this->id;
		$model = Inflector::singularize($class_name);
		$return = HeliumDB::straight_find($model, array($foreign_key => $id));

		$this->$class_name = $return;
	}
	
	protected function __map_many_to_many_relation($foreign_table, $join_table) {
		$db = Helium::db();

		$foreign_single = Inflector::singularize($foreign_table);
		$local_single = Inflector::singularize($this->__table);
		$foreign_key = $foreign_single . '_id';
		$local_key = $local_single . '_id';

		$query = "SELECT `$foreign_key` FROM `$join_table` WHERE `$local_key`='{$this->id}'";
		$foreign_ids = $db->get_col($query);

		if (!$foreign_ids || !is_array($foreign_ids))
			return false;

		$foreign_class_name = Inflector::camelize($foreign_single);
		$foreigners = array();
		foreach ($foreign_ids as $foreign_id) {
			$dummy = HeliumDB::straight_find($foreign_single, $foreign_id);
			if ($dummy)
			        $foreigners[] = $dummy;
		}

		$this->$foreign_table = $foreigners;

		return true;
	}
	

	private function __map_relations() {
		foreach ($this->__singular_relations as $class_name => $field)
			$this->__map_singular_relation($class_name, $field);

		foreach ($this->__plural_relations as $table_name => $foreign_key)
			$this->__map_plural_relation($table_name, $foreign_key);
			
		foreach ($this->__many_to_many_relations as $foreign_table => $join_table)
			$this->__map_many_to_many_relation($foreign_table, $join_table);
	}

	private function __refresh_relations() {
		foreach ($this->__singular_relations as $class_name => $field) {
			if (!$this->$field) // null, 0, false, means same thing
				$this->$class_name = null;
			elseif ($field != $this->$class_name->id)
				$this->__map_singular_relation($class_name, $field);
		}
	}

	private function __convert_columns() {
		foreach ($this->__column_types as $field => $type) {
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

	private function __db_values() {
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

			$fields[$field] = $value;
		}

		return $fields;
	}

	public function merge($source) {
		if (is_object($source))
			$source = get_object_vars($source);
		if (!is_array($source))
			return false;

		foreach ($this->__columns() as $column) {
			if ($source[$column] != $this->$column)
				$this->$column = $source[$column];
		}

		return true;
	}

	public function link($class) {
		$class_name = get_class($class);
		$single_name = Inflector::underscore($class_name);
		$plural_name = Inflector::tableize($class_name);
		$field = $this->__singular_relations[$single_name];
        
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

	public function save() {
		$this->__save();
	}

    protected function __save() {
        $db = Helium::db();

        $table = $this->__table;

		if ($this->__exists) {
			$query = array();
			foreach ($this->__db_values() as $field => $value) {
				$query[] = "`$field`='$value'";
			}
			if (in_array('updated_at', $this->__columns()))
				$query[] = "`updated_at`=NOW()";

		    $query = implode(', ', $query);

			$id = $this->id;
			$query = "UPDATE $table SET $query WHERE `id`='$id'";

			$query = $db->query($query);

			$this->__refresh_relations();
		}
		else {
			$fields = $values = array();
			foreach ($this->__columns() as $field) {
				if (!$this->$field || $field == 'created_at' || $field == 'updated_at')
					continue;

				$fields[] = "`$field`";
				$values[] = "'" . $db->escape($this->$field) . "'";
			}

			if (in_array('created_at', $this->__columns())) {
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

		$this->__exists = true;

        return true;
    }

	public function destroy() {
		$this->__destroy();
	}

	protected function __destroy() {
		$db = Helium::db();

		$table = $this->__table;
		$id = $this->id;

		$query = $db->query("DELETE FROM `$table` WHERE `id`='$id'");

		if ($query) {
			$unset = $this->__columns();
			foreach ($unset as $field)
				$this->$field = null;
		}
	}
	
	public function __toString() {
		if ($this->name)
			return $this->name;
		elseif ($this->name)
			return $this->name;
		elseif ($this->description)
			return $this->description;
		elseif ($this->text)
			return $this->text;
		else
			return get_class($this);
	}
}


