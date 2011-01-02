<?php

// HeliumRecordCollection
// An Iterator for HeliumRecord objects.
// Basically, it's lazy loading of database requests.

// a 'set' refers to a 'request' corresponding to a SELECT query.
// iterating on a set returns a batch of rows.
// a batch consists of several rows, corresponding to a LIMIT statement.
// the length of each batch can be controlled in an object
// and switching batches can also be done.
// (think of it like pages in a blog, each containing posts)

class HeliumRecordCollection extends HeliumRecordSupport implements Iterator {

	const all_records = null;

	public $model_name;
	public $table_name;
	public $class_name;

	private $conditions_array = array();
	private $additional_conditions_array = array();
	private $additional_where_approach = 'OR';
	private $conditions_string = '1';

	private $order_by = 'id';
	private $order = 'ASC';

	private $batch_number = 1;
	private $batch_start = 0;
	private $batch_length = 200;

	// joining variables
	private $one_to_one_associations = array();
	private $many_to_many_associations = array();
	private $join_statements = array();

	// loading variables
	private $fetched = false;
	private $query = ''; // the aggregate SQL query used in fetching
	public $rows = array(); // array of plain rows StdClass objects.
	private $count = 0;
	public $col_info = array();
	public $col_types = array();

	// iteration variables
	private $records = array();
	private $index = 0;
	private $prepared_index = 0;

	public function __construct($class_name) {
		$this->class_name = $class_name;
		$this->model_name = Inflector::underscore($class_name);

		$this->table_name = $this->get_model_table($this->model_name);

		$prototype = new $class_name;
		$this->one_to_one_associations = $prototype->_associations['one-to-one'];
		$this->many_to_many_associations = $prototype->_associations['many-to-many'];

		$base_join_statement = ' LEFT JOIN `%s` ON %s';
		$local_table = $this->table_name;

		foreach ($this->one_to_one_associations as $association_id => $options) {
			extract($options);
			$foreign_table = $this->get_model_table($class_name);
			if ($foreign_table) {
				$join_condition = "`$foreign_table`.`id`=`$local_table`.`$local_key`";
				$this->join_statements[] = sprintf($base_join_statement, $foreign_table, $join_condition);
			}
		}

		foreach ($this->many_to_many_associations as $association_id => $options) {
			extract($options);
			$join_condition = "`$join_table`.`$local_key`=`$local_table`.id";
			$this->join_statements[] = sprintf($base_join_statement, $join_table, $join_condition);
		}
	}

	private function get_model_table($class_name = '') {
		if (class_exists($class_name)) {
			$prototype = new $class_name;

			return $prototype->_table_name;
		}
	}

	public function fetch() {
		if ($this->fetched)
			return;

		// initialize
		$this->rows = array();
		$this->count = 0;
		$this->query = '';
		$this->records = array();
		$this->index = 0;
		$this->prepared_index = 0;

		$db = Helium::db();

		// process WHERE conditions
		if (count($this->additional_conditions_array) > 0) {
			if (count($this->conditions_array) > 0)
				$this->additional_conditions_array[] = $this->conditions_array;

			$subconditions = array();
			foreach ($this->additional_conditions_array as $condition) {
				if ($condition) // not empty array
					$subconditions[] = $this->generate_conditions_string($condition);
			}
			$this->conditions_string = implode(" {$this->additional_where_approach} ", $subconditions);
		}
		elseif (count($this->conditions_array) > 0)
			$this->conditions_string = $this->generate_conditions_string($this->conditions_array);

		// make the query
		$base_query = 'SELECT `%s`.* FROM `%1$s`%s WHERE %s ORDER BY `%s` %s';

		$join_clause = implode('', $this->join_statements);
		
		$query = sprintf($base_query, $this->table_name, $join_clause, $this->conditions_string, $this->order_by, $this->order);

		if ($this->batch_length >= 0)
			$query .= sprintf(' LIMIT %d,%d', $this->batch_start, $this->batch_length);

		// query the db
		$results = $db->get_results($query);
		$this->query = $query;
		$this->rows = $results;
		$this->count = count($results);

		$this->col_info = $db->col_info;
		foreach ($this->col_info as $col) {
			$name = $col->name;
			$type = $col->type;
			switch ($type) {
				case MYSQLI_TYPE_TINY:
					if ($length == 1)
						$type = 'bool';
				case MYSQLI_TYPE_SHORT:
				case MYSQLI_TYPE_LONG:
				case MYSQLI_TYPE_LONGLONG:
				case MYSQLI_TYPE_INT24:
					$type = 'int';
					break;
				case MYSQLI_TYPE_FLOAT:
				case MYSQLI_TYPE_DOUBLE:
				case MYSQLI_TYPE_DECIMAL:
				case MYSQLI_TYPE_NEWDECIMAL:
					$type = 'float';
					break;
				case MYSQLI_TYPE_DATE:
				case MYSQLI_TYPE_TIME:
				case MYSQLI_TYPE_DATETIME:
				case MYSQLI_TYPE_NEWDATE:
				case MYSQLI_TYPE_TIMESTAMP:
				case MYSQLI_TYPE_YEAR:
					$type = 'datetime';
					break;
				// to do: support the other column types
				default:
					$type = 'string';
			}
			$this->col_types[$name] = $type;
		}

		// make record objects from each row
		if (is_array($results)) {
			$this->fetched = true;
		}
		else {
			$this->records = array();
		}
	}

	private function generate_conditions_string(Array $array) {
		if (!$array) // empty array
			return '';

		$db = Helium::db();

		$query = array();
        foreach ($array as $field => $value) {
			$value = $db->escape($value);
			$field_particles = explode('.', $field);
			array_walk($field_particles, function(&$particle) {
				$particle = "`$particle`";
			});
			$field = implode('.', $field_particles);
            $query[] = "{$field}='{$value}'";
		}
		$conditions_string = '(' . implode(" AND ", $query) . ')';

		return $conditions_string;
	}

	public function first() {
		if (!$this->fetched) {
			$bl = $this->batch_length;
			$this->batch_length = 1;
			$this->fetch();
			$this->batch_length = $bl;
		}

		$this->rewind();

		return $this->current();
	}

	public function set_conditions_array(Array $conditions_array) {
		$this->fetched = false;
		$this->conditions_array = $conditions_array;
	}

	public function set_conditions_string($conditions_string) {
		$this->fetched = false;
		$this->conditions_string = trim($conditions_string);
	}
	
	public function set_batch_length($batch_length) {
		$this->fetched = false;
		$this->batch_length = $batch_length;
	}
	
	public function set_batch_number($batch_number) {
		$this->fetched = false;
		$this->batch_number = $batch_number;
		$this->batch_start = $batch_number * $batch_length;
	}
	
	public function set_order($order) {
		$this->fetched = false;
		$this->order = strtoupper($order);
	}
	
	public function set_order_by($field, $order = '') {
		$this->fetched = false;
		$this->order_by = $field;
		if ($order)
			$this->set_order($order);
	}
	
	public function count() {
		$this->fetch();

		return $this->count;
	}

	public function widen(Array $conditions) {
		$this->fetched = false;
		$this->additional_where_approach = 'OR';
		$this->additional_conditions_array[] = $conditions;
	}

	public function narrow(Array $conditions) {
		$this->fetched = false;
		$this->additional_where_approach = 'AND';
		$this->additional_conditions_array[] = $conditions;
	}

	public function add_ID($id) {
		$id = (string) Helium::numval($id);
		$this->widen(array('id' => $id));
	}

	private function prepare_record($row) {
		$class_name = $this->class_name;
		$record = new $class_name;

		foreach ($this->col_types as $name => $type) {
			$raw_value = $row->$name;
			$prepared_value = $this->prepare_value($raw_value, $type);
			$record->$name = $prepared_value;
		}

		$record->_column_types = $this->col_types;
		$record->_associate = $this->_associate;
		$record->_exists = true;
		$record->rebuild();

		return $record;
	}

	private function prepare_value($value, $type) {
		switch ($type) {
			case 'bool':
				$value = (bool) $value;
			case 'int':
				$value = Helium::numval($value);
				break;
			case 'float':
				$value = floatval($value);
				break;
			case 'datetime':
				$value = new DateTime($value);
				break;
			// default: $value is a string, let it be
		}

		return $value;
	}

	// iterator methods
	// we're only using numerical indices
	// so there's no need to use array_keys() like on php.net.

	public function rewind() {
		$this->fetch();

		$this->index = 0;
	}

	public function current() {
		$this->fetch();

		if ($this->prepared_index <= $this->index) {
			$row = $this->rows[$this->index];
			$record = $this->prepare_record($row);
			$this->records[$this->index] = $record;
			$this->prepared_index++;
		}

		$current_record = $this->records[$this->index];

		return $current_record;
	}
	
	public function key() {
		return $this->index;
	}
	
	public function next() {
		$this->index++;

		if ($this->index < $this->count)
			return $this->current();
	}
	
	public function valid() {
		return $this->index < $this->count;
	}
}