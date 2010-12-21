<?php

// HeliumRecord
// Helium's implementation of the Active Record model.

// goals: to not pollute the class namespace with unnecessary methods and properties.
// internal methods and properties are to be prefixed with an underscore,
// and must be defined as either private or protected.

// share the variable scope of association variables
abstract class HeliumRecordSupport {

	protected $_associations = array('one-to-one' => array(),
									'one-to-many' => array(),
									'many-to-many' => array());

	protected $_table_name = '';
	protected $_associate = ''; // the name of the model that is associated to this one.
}

abstract class HeliumRecord extends HeliumRecordSupport {

	// true if the record exists in the database.
	public $exists = false;

	protected $_model = ''; // a lowercase, underscored version of the class name

	protected $_columns = array();
	protected $_column_types = array();

	public function __construct() {
		$class_name = get_class($this);

		if (!$this->_table_name) {
			$table = Inflector::tableize($class_name);
			$this->_table_name = $table;
		}

		$model = Inflector::underscore($class_name);
		$this->_model = $model;

		$this->init();
	}

	/* blank methods
	   functions that can be redefined by child functions */

	// init function
	// association definitions (has_many, etc) go here
	// everything else that should be called during __construction should also be called here.
	public function init() {}

	// rebuild, called:
	// after a record is fetched from the database
	// after a record is saved (if after_save is not redefined)
	public function rebuild() {}

	// called at the beginning of save()
	public function before_save() {}
	
	// called at the end of save()
	// defaults to calling rebuild()
	public function after_save() {
		$this->rebuild();
	}

	/* finding functions
	   functions for and related to fetching records from the DB */

	final public static function find($conditions = null) {
		if (is_numeric($conditions)) { // we're looking for a single record with an ID.
			$multiple = self::find(array('id' => $conditions));
			return $multiple->single();
		}

		$class_name = get_called_class();
		$set = new HeliumRecordSet($class_name);

		if (is_array($conditions))
			$set->set_conditions_array($conditions);
		elseif (is_string($conditions) && $conditions != 'all')
			$set->set_conditions_string($conditions);

		return $set;
	}

	// invoking the object as a function fills it with data
	final public function __invoke(StdClass $result) {
		foreach ($result as $column => $value) {
			$this->$column = $value;
		}

		$this->exists = true;
		$this->_convert_columns();

		$this->rebuild();

		return $this;
	}

	// associational functions

	final protected function has_one($model_name, $options = array()) {
		extract($options);
		if (!$foreign_key)
			$foreign_key = $this->_model . '_id';
		if (!$class_name)
			$class_name = Inflector::camelize($association_id);
		if (!$conditions)
			$conditions = array();

		$_type = 'has_one';
		$this->_associations['one-to-one'][$model_name] = compact($_type, $foreign_key, $class_name, $conditions);
	}

	final protected function belongs_to($association_id, $options = array()) {
		extract($options);
		if (!$foreign_key)
			$foreign_key = $association_id . '_id';
		if (!$class_name)
			$class_name = Inflector::camelize($association_id);
		if (!$conditions)
			$conditions = array();
		
		$_type = 'belongs_to';
		$this->_associations['one-to-one'][$association_id] = compact($_type, $foreign_key, $class_name, $conditions);
	}

	final protected function has_many($association_id, $options) {
		extract($options);
		if (!$foreign_key)
			$foreign_key = $this->_model . '_id';
		if (!$class_name)
			$class_name = Inflector::camelize(Inflector::singularize($association_id));
		if (!$conditions)
			$conditions = array();

		$this->_associations['one-to-many'][$association_id] = compact($foreign_key, $class_name, $conditions);
	}

	// the other class must also declare has_and_belongs_to_many
	final protected function has_and_belongs_to_many($association_id, $options) {
		extract($options);

		if (!$class_name)
			$class_name = Inflector::classify($association_id);
		if (!$join_table) {
			$sort = array(Inflector::tableize($class_name), $this->_table_name);
			sort($sort);
			$join_table = implode('_', $sort);
		}
		if (!$foreign_key)
			$foreign_key = $this->_model . '_id';
		if (!$association_foreign_key)
			$association_foreign_key = Inflector::underscore($class_name) . '_id';
		if (!$conditions)
			$conditions = array();

		$this->_associations['many-to-many'][$association_id] = compact($class_name, $join_table, $foreign_key, $association_foreign_key, $conditions);
	}

	// internal mapping functions for associations

	final private function _map_one_to_one_association($association_id, $options) {
		extract($options);

		if ($_type == 'has_one')
			$conditions[$foreign_key] = $this->id;
		else
			$conditions['id'] = $this->$foreign_key;

		$return = $class_name::find($conditions);
		$return->_associate = $this;
		$return = $return->single();

		$this->$association_id = $return;

		return $return;
	}

	final private function _map_one_to_many_association($association_id, $options) {
		extract($options);
		
		$return = array();

		$conditions[$foreign_key] = $this->id;
		$return = $class_name::find($conditions);
		$return->_associate = $this;

		$this->$association_id = $return;

		return $return;
	}

	final protected function _map_many_to_many_association($association_id, $options) {
		if (!$this->id === null)
			return;

		extract($options);

		$associates = $class_name::find("`$join_table`.`$foreign_key`='{$this->id}'");
		if ($conditions)
			$associates->narrow($conditions);

		$associates->_associate = $this;

		$this->$association_id = $associates;

		return $associates;
	}

	// overloading for association support

	final public function __get($association_id) {
		if ($options = $this->_associations['one-to-one'][$association_id]) {
			$this->_map_one_to_one_association($association_id, $options);
			return $this->$association_id;
		}
		else if ($options = $this->_associations['one-to-many'][$association_id]) {
			$this->_map_one_to_many_association($association_id, $options);
			return $this->$association_id;
		}
		else if ($options = $this->_associations['many-to-many'][$association_id]) {
			$this->_map_many_to_many_association($association_id, $options);
			return $this->$association_id;
		}
		else
			return null;
	}

	final public function __isset($name) {
		return ($this->_associations['one-to-one'][$name] || 
				$this->_associations['one-to-many'][$name] ||
				$this->_associations['many-to-many'][$name]);
	}

	// manipulation functions

	public function save() {
		$db = Helium::db();

		$table = $this->_table_name;

		$this->before_save();

		if ($this->exists) {
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
		}
		else {
			$fields = $values = array();
			foreach ($this->_db_values() as $field => $value) {
				if (!$this->$field || $field == 'created_at' || $field == 'updated_at')
					continue;

				$fields[] = "`$field`";
				$values[] = "'$value'";
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

		$this->exists = true;

		$this->after_save();

		return true;
	}

	// to extend in child classes, call parent::destroy();
	public function destroy() {
		$db = Helium::db();

		$table = $this->_table_name;
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
			$table = $this->_table_name;
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
		$this->_columns(); // to fetch the column types if not yet fetched

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

		$this->_columns();

		foreach ($this->_column_types as $field => $type) {
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
				$value = $db->timestamp_to_string($value, $type);
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
		$field = $this->_associations['one-to-one'][$single_name];

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

	public function disassociate() {
		if (!$this->_associate)
			return;
		
		$associate = $this->_associate;
		$this_class = get_class($this);

		foreach ($associate->_associations as $type => $associations) {
			foreach ($associations as $association) {
				if ($association['class_name'] == $this_class) {
					$foreign_key = $association['foreign_key'];
					switch ($type) {
						case 'one-to-one':
							if ($association['_type'] == 'has_one') {
								$this->$foreign_key = 0;
								$this->save();
							}
							else {
								$associate->$foreign_key = 0;
								$associate->save();
							}
							break;
						case 'one-to-many':
							$this->$foreign_key = 0;
							$this->save();
							break;
						case 'many-to-many':
							$db = Helium::db();
							extract($association);
							$query = "DELETE FROM `$join_table` WHERE `$foreign_key`='{$associate->id}' AND `$association_foreign_key`='{$this->id}";
							return $db->query($query);
							break;
					}
				}
			}
		}
	}
}


