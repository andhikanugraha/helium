<?php

// HeliumDB
// original code from ezSQL by Justin Vincent
// with a lot of modifications to use mysqli
// Helium::db();

define('OBJECT', 'OBJECT', true);
define('ARRAY_A', 'ARRAY_A', true);
define('ARRAY_N', 'ARRAY_N', true);

final class HeliumDB {

	public $num_queries = 0;
	public $last_query = '';
	public $col_info = array();

	private $mysqli;

	/**********************************************************************
	*  Try to connect to mySQL database server and select a DB
	*/

	public function connect($dbuser='', $dbpassword='', $dbhost='localhost') {
		if ($this->mysqli)
			return;

		$dbuser = $this->db_user;
		$dbpassword = $this->db_pass;
		$dbhost = $this->db_host;
		$dbname = $this->db_name;

		// Must have a user and a password
		if (!$dbuser)
			throw new HeliumException(HeliumException::db_error, 'Connection to the database failed. No username was specified.');
		if (!$dbname )
			throw new HeliumException(HeliumException::db_error, 'Connection to the database failed. No database was selected.');

		// Try to establish the server database handle
		$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);
		if ($mysqli->connect_error)
			throw new HeliumException(HeliumException::db_error, "Connection to the database failed ({$mysqli->connect_errno}): {$mysqli->connect_error}");

		$this->mysqli = $mysqli;

		return true;
	}

	/**********************************************************************
	*  Format a mySQL string correctly for safe mySQL insert
	*/

	public function escape($str) {
		$this->connect();

		return $this->mysqli->real_escape_string($str);
	}

	/**********************************************************************
	*  Perform mySQL query and try to determine result value
	*/

	public function query($query) {
		// lazy connect
		$this->connect();

		$return_val = 0;
		$this->flush();

		// Keep track of the last query for debug..
		$this->last_query = $query;

		$mysqli = $this->mysqli;

		$this->result = $mysqli->query($query);
		$this->num_queries++;

		// If there is an error then throw an exception
		if ($mysqli->error)
			throw new HeliumException(HeliumException::db_error, $mysqli->error);

		if ( preg_match( "/^\\s*(insert|delete|update|replace|alter) /i", $query ) ) {
			$this->rows_affected = $mysqli->affected_rows;

			// Take note of the insert_id
			if ( preg_match( "/^\\s*(insert|replace) /i", $query ) ) {
				$this->insert_id = $mysqli->insert_id;
			}
			// Return number of rows affected
			$return_val = $mysqli->rows_affected;

		}
		else { // it was a SELECT query
			$i = 0;
			while ($col_info = $this->result->fetch_field()) {
				$this->col_info[$i] = $col_info;
				$i++;
			}

			$num_rows = 0;
			while ( $row = $this->result->fetch_object() ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			$this->result->free();

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	/**********************************************************************
	*  Kill cached query results
	*/

	public function flush() {
		$this->last_result = null;
		$this->col_info = null;
		$this->last_query = null;
	}

	/**********************************************************************
	*  Get one variable from the DB - see docs for more detail
	*/

	public function get_var($query=null, $x=0, $y=0) {

		// If there is a query then perform it if not then use cached results..
		if ($query) {
			$this->query($query);
		}

		// Extract var out of cached results based x,y vals
		if ($this->last_result[$y]) {
			$values = array_values(get_object_vars($this->last_result[$y]));
		}

		// If there is a value return it else return null
		return (isset($values[$x]) && $values[$x]!=='') ? $values[$x] : null;
	}

	/**********************************************************************
	*  Get one row from the DB - see docs for more detail
	*/

	public function get_row($query=null, $output=OBJECT, $y=0) {

		// If there is a query then perform it if not then use cached results..
		if ($query)
			$this->query($query);

		// If the output is an object then return object using the row offset..
		if ($output == OBJECT)
			return $this->last_result[$y] ? $this->last_result[$y] : null;

		// If the output is an associative array then return row as such..
		elseif ($output == ARRAY_A)
			return $this->last_result[$y] ? get_object_vars($this->last_result[$y]) : null;

		// If the output is an numerical array then return row as such..
		elseif ($output == ARRAY_N)
			return $this->last_result[$y] ? array_values(get_object_vars($this->last_result[$y])) : null;

		// If invalid output type was specified..
		else
			throw new HeliumException(HeliumException::db_error, 'HeliumDB::get_row() – Output type must be one of: OBJECT, ARRAY_A, ARRAY_N');

	}

	/**********************************************************************
	*  public function to get 1 column from the cached result set based in X index
	*  see docs for usage and info
	*/

	public function get_col($query=null, $x=0) {

		// If there is a query then perform it if not then use cached results..
		if ($query)
			$this->query($query);

		// Extract the column values
		for ($i=0; $i < count($this->last_result); $i++)
			$new_array[$i] = $this->get_var(null, $x, $i);

		return $new_array;
	}


	/**********************************************************************
	*  Return the the query as a result set
	*/

	public function get_results($query=null, $output = OBJECT) {

		// If there is a query then perform it if not then use cached results..
		if ($query)
			$this->query($query);

		// Send back array of objects. Each row is an object
		if ($output == OBJECT)
			return $this->last_result;

		elseif ($output == ARRAY_A || $output == ARRAY_N) {
			if ($this->last_result) {
				$i=0;

				foreach($this->last_result as $row) {
					$new_array[$i] = get_object_vars($row);

					if ($output == ARRAY_N)
						$new_array[$i] = array_values($new_array[$i]);

					$i++;
				}

				return $new_array;
			}
			else {
				return null;
			}
		}
	}

	/**********************************************************************
	*  Function to get column meta data info pertaining to the last query
	*/

	public function get_col_info($info_type="name",$col_offset=-1) {

		if ( $this->col_info ) {
			if ( $col_offset == -1 ) {
				$i=0;
				foreach($this->col_info as $col ) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			}
			else {
				return $this->col_info[$col_offset]->{$info_type};
			}
		}

	}

}
