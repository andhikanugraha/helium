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

	public $where_conditions = array();
	public $alternative_where_conditions = array();
	public $secondary_where_approach = 'OR';
	public $where_clause = '1';

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
		$this->model_name = Inflector::underscore($model_name);
		$this->table_name = Inflector::pluralize($this->model_name);
	}

	private function fetch() {
		if ($this->fetched)
			return;

		$db = Helium::db();

		// process WHERE conditions
		if (count($this->alternative_where_conditions) > 0) {
			$this->alternative_where_conditions[] = $this->where_conditions;
			$subconditions = array();
			foreach ($this->alternative_where_conditions as $condition) {
				$subconditions[] = $this->generate_where_clause($condition);
			}
			$this->where_clause = implode(" {$this->secondary_where_approach} ", $subconditions);
		}
		elseif (count($this->where_conditions) > 0)
			$this->where_clause = $this->generate_where_clause($this->where_conditions);

		// make the query
		$base_query = 'SELECT * FROM `%s` WHERE %s ORDER BY %s LIMIT %d,%d';
		$query = sprintf($base_query, $this->table_name, $this->where_clause, $this->order_by, $this->batch_start, $this->batch_length);

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

	public function generate_where_clause(Array $array) {
		$db = Helium::db();

		$query = array();
        foreach ($array as $field => $value) {
			$value = $db->escape($value);
            $query[] = "`$field`='{$value}'";
		}
		$where_clause = implode(" AND ", $query);

		return $where_clause;
	}

	public function set_where_conditions(Array $where_conditions) {
		$this->fetched = false;
		$this->where_conditions = $where_conditions;
	}

	public function widen(Array $conditions) {
		$this->fetched = false;
		$this->secondary_where_approach = 'OR';
		$this->alternative_where_conditions[] = $conditions;
	}

	public function narrow(Array $conditions) {
		$this->fetched = false;
		$this->secondary_where_approach = 'AND';
		$this->alternative_where_conditions[] = $conditions;
	}

	public function add_ID($id) {
		$id = (string) Helium::numval($id);
		$this->widen(array('id' => $id));
	}

	// iterator methods

	public function rewind() {
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