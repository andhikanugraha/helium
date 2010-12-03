<?php

// HeliumDB
// extends ezSQL_mySQL
// Helium::db();

require_once 'db/ez_sql_core.php';
require_once 'db/ez_sql_mysql.php';
require_once 'record.php';

final class HeliumDB extends ezSQL_mySQL {
	const all = null;

    protected static $mUser;
    protected static $mPassword;
    protected static $mName;
    protected static $mHost = 'localhost';

    private static $instance;

    public function __construct() {
        parent::ezSQL_mySQL();
    }

    public static function configure($user, $pass, $db = '', $host = 'localhost', $override = true) {
        if ($override) {
            self::$mUser = $user;
            self::$mPassword = $pass;
            self::$mName = $db ? $db : $user;
            self::$mHost = $host;
        }
        else {
            if (!self::$mUser && !self::$mPassword) {
                self::$mUser = $user;
                self::$mPassword = $pass;
            }

            if (!self::$mName) {
                self::$mName = $db ? $db : $user;
            }

            if (self::$mHost == 'localhost') {
                self::$mHost = $host;
            }
        }
    }

    public function connect($dbuser = '', $dbpassword = '', $dbhost = 'localhost') {
        $dbuser = $this->dbuser = self::$mUser;
        $dbpassword = $this->dbpassword = self::$mPassword;
        $dbname = $this->dbname = self::$mName;
        $dbhost = $this->dbhost = self::$mHost;

        parent::connect($dbuser, $dbpassword, $dbhost);
        parent::select($dbname);
    }

    public function escape($string = false) {
        $this->connect();
        if ($string)
            return mysql_real_escape_string($string);
    }

	public function timetostr($timestamp, $column_type = 'datetime') {
		$datestrings = array('datetime' => 'Y-m-d H:i:s', 'date' => 'Y-m-d');
		$column_type = strtolower($column_type);
		if ($column_type == 'timestamp')
			$column_type = 'datetime';

		if (!$datastrings[$column_type])
			return $timestamp;
			
		$timestamp = intval($timestamp);
		return date($datestrings[$timestamp], $timestamp);
	}

    private static function find_query($class, $conditions = null, $single = false) {
		// if (!$class)
		// 	return;

        $table_name = Inflector::pluralize($class);

        $query = "SELECT * FROM $table_name";

		$where_clause = "WHERE 1=0";
		
		if ($conditions === self::all) {
			$where_clause = "WHERE 1";
		}
        elseif (is_numeric($conditions)) {
            $conditions = "id=$conditions";
            $single = true;
			$where_clause = "WHERE $conditions";
        }
		elseif (is_array($conditions)) {
			$conditions = self::stringify_where_clause($conditions);
			$where_clause = "WHERE $conditions";
		}
		elseif (is_string($conditions)) {
			$conditions = trim($conditions);
			if (strtoupper(substr($conditions, 0, 5)) == 'WHERE')
				$where_clause = $conditions;
		}

		$query .= ' ' . $where_clause;
		if ($single)
			$query .= ' LIMIT 1';

        $db = $this ? $this : Helium::db();
        $query = $db->get_results($query);

		if (!$query)
			return array();
		else
			return $query;
	}

	public static function straight_find($class, $conditions = null, $single = false) {
        if (is_numeric($conditions))
            $single = true;
		$query = self::find_query($class, $conditions, $single);

		$return = array();
		$class_name = Inflector::camelize($class);
        foreach ($query as $row) {
            $dummy = new $class_name;
            foreach (get_object_vars($row) as $var => $value)
                $dummy->$var = $value;

			$dummy->__found(true);

			$return[] = $dummy;
        }

        if ($single)
            return $return[0];

        return $return;
    }

	public static function find($class, $conditions = null, $single = false) {
        if (is_numeric($conditions))
            $single = true;
		$query = self::find_query($class, $conditions, $single);

		$return = array();
		$class_name = Inflector::camelize($class);
        foreach ($query as $row) {
            $dummy = new $class_name;
            foreach (get_object_vars($row) as $var => $value)
                $dummy->$var = $value;

			$dummy->__found(false);

			$return[] = $dummy;
        }

        if ($single)
            return $return[0];

        return $return;
    }

	private static function stringify_where_clause($array) {
		$db = $this ? $this : Helium::db();
		$query = array();
        foreach ($array as $field => $value) {
			$value = $db->escape($value);
            $query[] = "`$field`='{$value}'";
		}
		$query = implode(' AND ', $query);

		return $query;
	}
}
