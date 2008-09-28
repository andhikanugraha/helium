<?php

// HeliumActiveRecord
// something like Rails' ActiveRecord::Base.

abstract class HeliumActiveRecord {
	private $__exists;
    private $__table;
    private $__columns = array();
	private $__column_types = array();
	private $__singular_relations = array();
	private $__plural_relations = array();
	private $__built = false;
	private $__serialize = array();

    public function __construct() {
        $table = get_class($this);
        $table = Inflector::tableize($table);
        $this->__table = $table;

		foreach ($this->__columns() as $column) {
			if (!isset($this->$column))
				$this->$column = '';
		}

		$this->__build();
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
			$return = HeliumActiveRecordSupport::find($model, array($foreign_key => $id));
		}
		else {
			$field = $this->__singular_relations[$class_name];
			if ($this->$field)
				$return = HeliumActiveRecordSupport::find($class_name, $this->$field);
			else
				$return = null;
		}

		$this->$class_name = $return;
		return $return;
	}

	// use __build as constructors for descendant classes
	public function __build() {}

	// called on find(), parses the SQL data
	public function __found() {
		if ($this->__exists)
			return true;

		$this->__exists = true;
		$this->__convert_columns();
		
		foreach ($this->__serialize as $field) {
			$value = $this->$field;

			if (strlen($value) > 0) {
				if ($zombie = @unserialize($value))
					$this->$field = $value;
			}
		}

		foreach ($this->__columns() as $field)
			$this->__map_relation($field);

		$this->__parse();
	}

	// this is called on find()
	public function __parse() {}
	
	protected static function __find($class, $args) {
		$class = Inflector::underscore($class);
		array_unshift($args, $class);
		return call_user_func_array(array('HeliumActiveRecordSupport', 'find'), $args);
	}

	// base HeliumActiveRecord::find() on HeliumActiveRecordSupport::find()
	abstract public static function find($class_name, $args);

	/*
	 * find() code example

	public static function find() {
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

	private function __map_relation($field) {
		$plural_relations_flip = array_flip($this->__plural_relations);
		$singular_relations_flip = array_flip($this->__singular_relations);

		if ($class_name = $plural_relations_flip[$field]) {
			$id = $this->id;
			$model = Inflector::singularize($class_name);
			$return = HeliumActiveRecordSupport::find($model, array($foreign_key => $id));
		}
		elseif ($class_name = $singular_relations_flip[$field]) {
			if ($this->$field)
				$return = HeliumActiveRecordSupport::find($class_name, $this->$field);
			else
				$return = null;
		}
		else
			return false;

		$this->$class_name = $return;

		return $return;
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
	}

	// from anything into string
	private function __revert_columns() {
		$return = array();
		foreach ($this->__columns as $field) {
			$value = $this->$field;

			if ($this->__serialize[$field])
				$value = serialize($value);
			else
				$value = (string) $value;

			$return[$field] = $value;
		}
	}

	public function save() {
		$this->__save();
	}

    protected function __save() {
		global $db;

        $table = $this->__table;

		if ($this->__exists) {
			$query = array();

			$values = $this->__revert_columns();
			
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
			foreach ($this->__columns() as $field) {
				if (!$this->$field || $field == 'created_at' || $field == 'updated_at')
					continue;

				$fields[] = "`$field`";
				$values[] = "'" . $db->escape($this->$field) . "'";
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

        return true;
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

	protected function serialize_field($field_name) {
		$this->__serialize[] = $field_name;
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
}

final class HeliumActiveRecordSupport {
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

        foreach ($query as $row) {
            $dummy = new $class;
            foreach (get_object_vars($row) as $var => $value)
                $dummy->$var = $value;

			$dummy->__found();

			$return[] = $dummy;
        }

        if ($single)
            return $return[0];

        return $return;
    }
}