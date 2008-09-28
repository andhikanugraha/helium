<?php

// Helium_ActiveRecord
// something like Rails' ActiveRecord::Base.

abstract class Helium_ActiveRecord {
	public $id = 0;
	public $created_at = 0;
	public $updated_at = 0;
	
	private $__exists;
    private $__table;
    private $__columns = array();
	private $__column_types = array();
	private $__singular_relations = array();
	private $__plural_relations = array();
	private $__built = false;
	private $__serializeds = array();
	private $__aliases = array();

    public function __construct() {
        $table = get_class($this);
        $table = Inflector::tableize($table);
        $this->__table = $table;

		foreach ($this->__columns() as $column) {
			if (!isset($this->$column)) {
				$this->$column = '';
			}
		}

		foreach ($this->__properties() as $property) {
			if (is_array($this->$property) || is_object($this->$property)) // is_object is highly unlikely, though.
				$this->serialize($property);
		}

		$this->__build();

		$this->__map_aliases();
    }

	public function __isset($name) {
		if (in_array($name, $this->__singular_relations))
			return true;
		elseif (in_array($name, $this->__plural_relations))
			return true;
		elseif ($this->$name)
			return true;
		else
			return false;
	}

	public function __get($class_name) {
		if (!$class_name)
			return;

		if ($foreign_key = $this->__plural_relations[$class_name]) {
			$id = $this->id;
			$model = Inflector::singularize($class_name);
			$return = Helium_ActiveRecord_Support::find($model, array($foreign_key => $id));
		}
		else {
			$field = $this->__singular_relations[$class_name];
			if ($this->$field)
				$return = Helium_ActiveRecord_Support::find($class_name, $this->$field);
			else
				$return = null;
		}

		$this->$class_name = $return;
		return $return;
	}

	// use __build as constructors for descendant classes
	public function __build() {
		$this->__rebuild();
	}

	public function __rebuild() {}

	// called on find(), parses the SQL data
	public function __found() {
		if ($this->__exists)
			return true;

		$this->__exists = true;
		$this->__convert_columns();
		
		foreach ($this->__serializeds as $field) {
			$value = $this->$field;

			if (is_string($value) && strlen($value) > 0) {
				if ($zombie = unserialize($value))
					$this->$field = $value;
			}
		}

		foreach ($this->__columns() as $field)
			$this->__map_singular_relation($field);

		$this->__map_plural_relations();

		$this->__rebuild();
	}
	
	protected static function __find($class, $arguments = array()) {
		$class = Inflector::underscore($class);
		array_unshift($arguments, $class);
		return call_user_func_array(array('Helium_ActiveRecord_Support', 'find'), $arguments);
	}

	// base Helium_ActiveRecord::find() on Helium_ActiveRecord_Support::find()
	abstract public static function find();

	/*
	 * find() code example

	public static function find($query) {
		return parent::__find(__CLASS__, func_get_args());
	}

	*/

    public function __columns() {
        if (!$this->__columns) {
			global $db;
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

	private function __properties() {
		static $return;
		if ($return)
			return $return;

		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties();
		
		$return = array();
		foreach ($properties as $property) {
			if (!$property->isStatic() && $property->isPublic())
				$return[] = $property->name;
		}

		return $return;
	}

	private function __map_singular_relation($field) {
		$singular_relations_flip = array_flip($this->__singular_relations);
		
		if ($class_name = $singular_relations_flip[$field]) {
			if ($this->$field)
				$return = Helium_ActiveRecord_Support::find($class_name, $this->$field);
			else
				$return = null;
		}
		else
			return false;

		$this->$class_name = $return;

		return $return;
	}

	private function __map_plural_relations() {
		static $key;
		foreach ($this->__plural_relations as $class_name => $foreign_key) {
			$id = $this->id;
			$model = Inflector::singularize($class_name);
			$return = Helium_ActiveRecord_Support::find($model, array($foreign_key => $id));
			if (!empty($return))
				$this->$class_name = $return;
		}
	}

	private function __convert_column($field) {
		$type = $this->__column_types[$field];
		
		$value = $this->$field;

		switch ($type) {
		case 'bool':
			$value = $value ? true : false;
			break;
		case 'int':
		case 'tinyint':
		case 'bigint':
			$value = intval($value);
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

	private function __convert_columns() {
		foreach ($this->__columns() as $field)
			$this->__convert_column($field);
	}

	// from anything into string
	private function __escape_fields() {
		$return = array();
		foreach ($this->__columns as $field) {
			$value = $this->$field;

			if (method_exists($this, 'filter_' . $field))
				$value = $this->{'filter_' . $field}();

			if ($this->__serializeds[$field])
				$value = serialize($value);
			else
				$value = (string) $value;

			$return[$field] = $value;
		}

		return $return;
	}

	public function save() {
		$this->__save();
		$this->__rebuild();
	}

    protected function __save() {
		global $db;

        $table = $this->__table;

		if ($this->__exists) {
			$query = array();

			$values = $this->__escape_fields();
			
			foreach ($values as $field => $value) {
				$value = $db->escape($value);
				$query[] = "`$field`='$value'";
			}
			$query[] = "`updated_at`=NOW()";

		    $query = implode(', ', $query);

			$id = $this->id;
			$query = "UPDATE $table SET $query WHERE `id`='$id'";

			$query = $db->query($query);
		}
		else {
			$fields = $values = array();
			foreach ($this->__escape_fields() as $field => $value) {
				if (!$this->$field || $field == 'created_at' || $field == 'updated_at')
					continue;

				$fields[] = "`$field`";
				$values[] = "'" . $db->escape($value) . "'";
			}

			$fields[] = "`created_at`";
			$values[] = "NOW()";

			$fields = implode(', ', $fields);
			$values = implode(', ', $values);

			$query = "INSERT INTO $table ($fields) VALUES ($values)";

			$query = $db->query($query);

			$this->id = $db->insert_id;
		}

        if (!$query)
            return false;

		$this->__exists = true;

        return $query;
    }

	public function destroy() {
		$this->__destroy();
	}

	protected function __destroy() {
		global $db;

		$table = $this->__table;
		$id = $this->id;

		$query = $db->query("DELETE FROM `$table` WHERE `id`='$id'");

		if ($query) {
			$unset = array_merge($this->__columns(), array_keys($this->__singular_relations), array_keys($this->plural_relations));
			foreach ($unset as $field)
				$this->$field = null;
		}
	}

	protected function serialize($field_name) {
		$this->__serializeds[] = $field_name;
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
		if ($this->__plural_relations[$class_name])
			return;
		if (!$foreign_key)
			$foreign_key = Inflector::singularize($this->__table) . '_id';

		$this->__plural_relations[$class_name] = $foreign_key;
	}

	protected function belongs_to_many($class_name, $foreign_key = null) {
		$this->has_many($class_name, $foreign_key);
	}

	protected function alias($one, $two) {
		$this->__aliases[] = array($one, $two);
	}

	private function __map_aliases() {
		foreach ($this->__aliases as $alias) {
			list($one, $two) = $alias;

			if (in_array($one, $this->__columns()))
				$this->$two = &$this->$one;
			else // not necessarily meaning that $this->$two is a column
				$this->$one = &$this->$two;
		}
	}

	public function get_columns() {
		return $this->__columns();
	}
}

class Helium_ActiveRecord_Support {
	const all = '1';
    public static function find($class, $conditions = '1', $single = false) {
        $table_name = Inflector::pluralize($class);

        if (is_int($conditions)) {
            $conditions = "id=$conditions";
            $single = true;
        }
		elseif (is_array($conditions)) {
			$conditions = self::stringify_where_clause($conditions);
		}

        $query = "SELECT * FROM $table_name";
		if ($conditions !== self::all)
			$query .= " WHERE $conditions";

        global $db;
        $query = $db->get_results($query);

		if (!$query)
			return false;
			
        $return = array();

		$model = Inflector::classify($class);
        foreach ($query as $row) {
            $dummy = new $model;
            foreach (get_object_vars($row) as $var => $value)
                $dummy->$var = $value;

			$dummy->__found();

			$return[] = $dummy;
        }

        if ($single)
            return $return[0];

        return $return;
    }

	public static function get_columns($model) {
		$class = Inflector::classify($model);
		$test = new $class;
		return $test->get_columns();
	}

	private static function stringify_where_clause($array) {
		if (is_object($array))
			$array = get_object_vars($array);
		if (!is_array($array))
			return false;

		global $db;
		$query = array();
        foreach ($array as $field => $value) {
			$value = $db->escape($value);
            $query[] = "`$field`='{$value}'";
		}
		$query = implode(' AND ', $query);

		return $query;
	}
}

function find_records() {
	$arguments = func_get_args();
	return call_user_func_array(array('Helium_ActiveRecord_Support', 'find'), $arguments);
}

function get_fields($model) {
	return Helium_ActiveRecord_Support::get_columns($model);
}