<?php

// Helium_ActiveRecord
// something like Rails' ActiveRecord::Base.

abstract class Helium_ActiveRecord {
	public $id = 0;
	public $created_at = 0;
	public $updated_at = 0;

	public $__has_one = array();
	public $__belongs_to = array();
	public $__has_and_belongs_to_many = array();
	public $__has_many = array();

	private $__exists;
    private $__table;
    private $__fields = array();
	private $__field_types = array();
	private $__unique_fields = array();
	private $__built = false;
	private $__serializeds = array();
	private $__aliases = array();

    public function __construct() {
        $table = get_class($this);
        $table = Inflector::tableize($table);
        $this->__table = $table;

		foreach ($this->__fields() as $field) {
			if (!isset($this->$field)) {
				$this->$field = '';
			}
		}

		foreach ($this->__properties() as $property) {
			if (is_array($this->$property))
				$this->serialize($property, 'array');
			if (is_array($this->$property))
				$this->serialize($property, 'object');
		}

		$this->__build();

		$this->__map_aliases();

		$this->__map_plural_relations();
    }

	public function __isset($name) {
		$combo = array_merge($this->__fields(), array_keys($this->__belongs_to), array_keys($this->__has_one), array_keys($this->__has_many), $this->__has_and_belongs_to_many);

		if (in_array($name, $combo))
			return true;

		if ($this->$name)
			return true;

		return false;
	}

	public function __get($class_name) {
		if (!$class_name)
			return;

		if ($foreign_key = $this->__has_many[$class_name]) {
			$model = Inflector::singularize($class_name);
			$return = find_records($model, array($foreign_key => $this->id));
		}
		elseif ($field = $this->__belongs_to[$class_name]) {
			$table = Inflector::singularize($this->__table);
			if ($this->$field)
				$return = find_first_record($class_name, $this->$field);
			else
				$return = null;
		}
		elseif ($field = $this->__has_one[$class_name]) {
			$model = Inflector::singularize($class_name);
			if ($this->$field)
				$return = find_first_record($model, array($field => $this->id));
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
		$this->__convert_fields();

		foreach ($this->__serializeds as $field => $type) {
			$value = $this->$field;

			if (is_string($value) && strlen($value) > 0) {
				if ($zombie = unserialize($value))
					$this->$field = $zombie;
			}
			elseif ($type == 'array')
				$this->$field = array();
		}

		foreach ($this->__belongs_to as $class_name => $field) {
			if ($this->$field)
				$this->$class_name = find_first_record($class_name, $this->$field);
		}
		foreach ($this->__has_one as $model => $field)
			$this->$field = find_first_record($model, array($field => $this->id));

		$this->__map_plural_relations();

		$this->__rebuild();
	}

	protected static function __find($class, $arguments = array()) {
		$class = Inflector::underscore($class);
		array_unshift($arguments, $class);
		return call_user_func_array(array('Helium_ActiveRecord_Support', 'find'), $arguments);
	}

	// base Helium_ActiveRecord::find() on Helium_ActiveRecord_Support::find()
	public static function find() {}

	// find() code example
	// 
	// public static function find() {
	// 	$arguments = func_get_args();
	// 	return parent::__find(__CLASS__, $arguments);
	// }

	// associations

	protected function has_one($class_name, $field = null) {
		if (!$field)
			$field = $class_name . '_id';

		$this->__has_one[$class_name] = $field;
	}

	protected function belongs_to($class_name, $field = null) {
		if (!$field)
			$field = $class_name . '_id';

		$this->__belongs_to[$class_name] = $field;
	}

	protected function has_many($class_name, $foreign_key = null) {
		if ($this->__has_many[$class_name])
			return;
		if (!$foreign_key)
			$foreign_key = Inflector::singularize($this->__table) . '_id';

		$this->__has_many[$class_name] = $foreign_key;
	}

	protected function has_and_belongs_to_many($class_name) {
		if ($this->__has_and_belongs_to_many[$class_name])
			return;

		$this->__has_and_belongs_to_many[] = $class_name;
	}

	// inherited functions

	public function save() {
		$save = $this->__save();
		$this->__rebuild();

		return $save;
	}

    protected function __save() {
		global $db;

        $table = $this->__table;

		if ($this->__exists) {
			$query = array();

			$values = $this->__escape_fields();

			foreach ($values as $field => $value) {
				if ($field == 'created_at')
					continue;
				if ($field == 'updated_at') {
					$query[] = "`updated_at`=NOW()";
					continue;
				}
				$value = $db->escape($value);
				$query[] = "`$field`='$value'";
			}

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

			$field_names = $this->__fields();
			if (in_array('created_at', $field_names)) {
				$fields[] = "`created_at`";
				$values[] = "NOW()";
			}
			if (in_array('updated_at', $field_names)) {
				$fields[] = "`updated_at`";
				$values[] = "NOW()";
			}

			$fields = implode(', ', $fields);
			$values = implode(', ', $values);

			$query = "INSERT INTO $table ($fields) VALUES ($values)";

			$query = $db->query($query);

			$this->id = $db->insert_id;
			$this->created_at = time();
			$this->updated_at = time();
		}

        if (!$query)
            return false;

		$this->__exists = true;

        return true;
    }

	public function destroy() {
		return $this->__destroy();
	}

	protected function __destroy() {
		global $db;

		$table = $this->__table;
		$id = $this->id;

		if (!$id)
			return;

		$query = $db->query("DELETE FROM `$table` WHERE `id`='$id'");

		if ($query) {
			$unset = array_merge($this->__fields(), array_keys($this->__belongs_to), array_keys($this->__has_one), array_keys($this->__has_many), $this->__has_and_belongs_to_many);
			foreach ($unset as $field)
				$this->$field = null;
		}

		return $query;
	}

	protected function serialize($field_name, $type = 'array') {
		if (!$this->__serializeds[$field_name])
			$this->__serializeds[$field_name] = $type;
	}

	protected function alias($one, $two) {
		$this->__aliases[] = array($one, $two);
	}

	// internal functions

	private function __map_plural_relations() {
		foreach ($this->__has_many as $class_name => $foreign_key) {
			$model = Inflector::singularize($class_name);
			$return = find_records($model, array($foreign_key => $this->id));
			if (!empty($return))
				$this->$class_name = $return;
		}
		foreach ($this->__has_and_belongs_to_many as $that_table) {
			$this_table = $this->__table;
			$this_model = Inflector::singularize($this_table);
			$that_model = Inflector::singularize($that_table);
			$this_key = $this_model . '_id';
			$that_key = $that_model . '_id';

			$tables = array($this_table, $that_table);
			sort($tables);
			$join_table = implode('_', $tables);

			$query = "SELECT `$that_table`.* FROM `$join_table` LEFT JOIN `$that_table` ON `$that_key`=`$that_table`.`id` WHERE `$join_table`.`$this_key`=$this->id";
			$this->$that_table = find_records_by_query($that_table, $query);
		}
	}

    public function __fields() {
        if (!$this->__fields) {
			global $db;
            $table = $this->__table;
            $query = $db->get_results("SHOW COLUMNS FROM `$table`");

			$fields = array();
			foreach ($query as $row) {
				$field = $row->Field;
				$type = $row->Type;

				$fields[] = $field;
				if ($type == 'tinyint(1)') // boolean
					$type = 'bool';
				elseif (($pos = strpos($type, '(')) > 0)
					$type = substr($type, 0, $pos);

				$this->__field_types[$field] = $type;

				$key = $row->Key;
				if (!empty($key))
					$this->__unique_fields[] = $key;
			}

            $this->__fields = $fields;
        }

        return $this->__fields;
    }

	private function __properties() {
		static $return;
		if ($return)
			return $return;

		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties();

		$return = array();
		$relations = array('__has_one', '__belongs_to','__has_many',  '__has_and_belongs_to_many');
		foreach ($properties as $property) {
			if (in_array($property->name, $relations))
				continue;
			if (!$property->isStatic() && $property->isPublic())
				$return[] = $property->name;
		}

		return $return;
	}

	private function __convert_fields() {
		foreach ($this->__fields() as $field)
			$this->__convert_field($field);
	}

	private function __convert_field($field) {
		$type = $this->__field_types[$field];

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

	private function __escape_fields() {
		$return = array();
		foreach ($this->__fields as $field) {
			$value = $this->$field;

			if (method_exists($this, 'filter_' . $field))
				$value = $this->{'filter_' . $field}();

			if ($this->__serializeds[$field]) {
				if (!empty($value) && !is_string($value))
					$value = serialize($value);
			}
			else {
				$value = (string) $value;
				switch ($this->__field_types[$field]) {
				case 'bool':
					$value = $value ? '1' : '0';
					break;
				case 'datetime':
				case 'date':
				case 'timestamp':
					if ($value)
						$value = date('Y-m-d H:i:s', $value);
					break;
				default:
					$value = (string) $value;
				}
			}

			$return[$field] = $value;
		}

		return $return;
	}

	private function __map_aliases() {
		foreach ($this->__aliases as $alias) {
			list($one, $two) = $alias;

			if (in_array($one, $this->__fields()))
				$this->$two = &$this->$one;
			else // not necessarily meaning that $this->$two is a field
				$this->$one = &$this->$two;
		}
	}

	public function __is_unique_field($field) {
		return in_array($field, $this->__unique_fields);
	}

	public function get_fields() {
		return $this->__fields();
	}

	public function get_field_types() {
		$types = $this->__field_types;
		foreach ($types as $field => $type) {
			switch($type) {
				case 'int':
				case 'tinyint':
				case 'bigint':
					$types[$field] = 'int';
					break;
				case 'datetime':
				case 'date':
				case 'timestamp':
					$types[$field] = 'date';
					break;
				case 'varchar':
				case 'char':
				case 'text':
					$types[$field] = 'string';
				default:
					$types[$field] = $type;
			}
		}

		return $types;
	}
}

class Helium_ActiveRecord_Support {
	const all = '1';

	public static function find_by_query($table, $query) {
		if (!$query)
			return;

		$model = Inflector::classify(Inflector::singularize($table));

		if (is_string($query)) {
			global $db;
			$query = $db->get_results($query);
		}

		if (is_array($query)) {
	        foreach ($query as $row) {
	            $dummy = new $model;

	            foreach (get_object_vars($row) as $var => $value)
	                $dummy->$var = $value;

				$dummy->__found();

				$return[] = $dummy;
	        }
		}
		elseif (is_object($query)) {
			$dummy = new $model;
            foreach (get_object_vars($query) as $var => $value)
                $dummy->$var = $value;
			$dummy->__found();

			$return = $dummy;
		}

        return $return;
	}

    public static function find($class, $conditions = '1', $single = false) {
        $table_name = Inflector::pluralize($class);

        if (is_int($conditions)) {
            $conditions = "`id`=$conditions";
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
		$scanned = false;
        foreach ($query as $row) {
            $dummy = new $model;

			if (!$single && !$scanned) {
				if (is_array($conditions)) {
					foreach (array_keys($conditions) as $key) {
						if ($dummy->__is_unique_field($key))
							$single = true;
					}
				}
				$scanned = true;
			}

            foreach (get_object_vars($row) as $var => $value)
                $dummy->$var = $value;

			$dummy->__found();

			$return[] = $dummy;
        }

        if ($single)
            return $return[0];

        return $return;
    }

	public static function get_fields($model) {
		$class = Inflector::classify($model);
		$test = new $class;
		return $test->get_fields();
	}

	public static function get_field_types($model) {
		$class = Inflector::classify($model);
		$test = new $class;
		return $test->get_field_types();
	}

	private static function stringify_where_clause($array) {
		if (is_object($array))
			$array = get_object_vars($array);
		if (!is_array($array))
			return false;

		global $db;
		$query = '1=1';
        foreach ($array as $field => $value) {
			$value = $db->escape($value);
            $query .= " AND `$field`='{$value}'";
		}

		return $query;
	}
}

function find_records() {
	$arguments = func_get_args();
	return call_user_func_array(array('Helium_ActiveRecord_Support', 'find'), $arguments);
}

function find_first_record() {
	$arguments = func_get_args();
	$return = call_user_func_array(array('Helium_ActiveRecord_Support', 'find'), $arguments);

	if (is_array($return))
		return $return[0];
	else
		return $return;
}

function find_records_by_query() {
	$arguments = func_get_args();
	return call_user_func_array(array('Helium_ActiveRecord_Support', 'find_by_query'), $arguments);
}

function get_fields($model) {
	return Helium_ActiveRecord_Support::get_fields($model);
}

function get_field_types($model) {
	return Helium_ActiveRecord_Support::get_field_types($model);
}