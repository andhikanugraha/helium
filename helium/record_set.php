<?php

// HeliumRecordSet
// An Iterator for HeliumRecord objects.
// Basically, it's lazy loading of database requests.

// a 'set' refers to a 'request' corresponding to a SELECT query.
// iterating on a set returns a batch of rows.
// a batch consists of several rows, corresponding to a LIMIT statement.
// the length of each batch can be controlled in an object
// and switching batches can also be done.
// (think of it like pages in a blog, each containing posts)

class HeliumRecordSet implements Iterator {

	const all_records = null;

	public $model_name;
	public $table_name;
	public $class_name;

	public $conditions_array = array();
	public $additional_conditions_array = array();
	public $additional_where_approach = 'OR';
	public $conditions_string = '1';

	public $order_by = '`id` ASC';

	public $batch_start = 0;
	public $batch_length = 200;

	// loading variables
	public $fetched = false;
	public $vanilla = array(); // array of plain vanilla StdClass objects.
	public $single = false; // the first fetched record

	// iteration variables
	private $records = array();
	private $index = 0;

	public function __construct($class_name) {
		$this->class_name = $class_name;
		$this->model_name = Inflector::underscore($class_name);
		$this->table_name = Inflector::tableize($class_name);
	}

	private function fetch() {
		if ($this->fetched)
			return;

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
		$base_query = 'SELECT * FROM `%s` WHERE %s ORDER BY %s LIMIT %d,%d';
		$query = sprintf($base_query, $this->table_name, $this->conditions_string, $this->order_by, $this->batch_start, $this->batch_length);

		// query the db
		$results = $db->get_results($query);
		$this->vanilla = $results;
		
		// make record objects from each row
		if (is_array($results)) {
			$records = array();
			$class_name = $this->class_name;
			foreach ($results as $row) {
				$record = new $class_name;
				$record($row);
				$records[] = $record;
			}

			$this->records = $records;
			$this->single = $records[0];
			$this->fetched = true;
		}
		else {
			$this->records = array();
			$this->single = false;
		}
	}

	public function generate_conditions_string(Array $array) {
		if (!$array) // empty array
			return '';

		$db = Helium::db();

		$query = array();
        foreach ($array as $field => $value) {
			$value = $db->escape($value);
            $query[] = "`$field`='{$value}'";
		}
		$conditions_string = '(' . implode(" AND ", $query) . ')';

		return $conditions_string;
	}

	public function set_conditions_array(Array $conditions_array) {
		$this->fetched = false;
		$this->conditions_array = $conditions_array;
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

	// iterator methods

	public function rewind() {
		$this->fetch();

		$this->index = 0;
	}
	
	public function current() {
		$this->fetch();

		$k = array_keys($this->records);
		$record = $this->records[$k[$this->index]];

		return $record;
	}
	
	public function key() {
		$k = array_keys($this->records);
		$key = $k[$this->index];
		
		return $key;
	}
	
	public function next() {
		$k = array_keys($this->records);
		if (isset($k[++$this->index])) {
			$record = $this->records[$k[$this->index]];
			
			return $record;
		}
		else {
			return false;
		}
	}
	
	public function valid() {
		$k = array_keys($this->records);
		$valid = isset($k[$this->index]);

		return $valid;
	}
}