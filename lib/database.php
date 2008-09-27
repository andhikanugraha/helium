<?php

// Helium framework
// class HeliumDatabaseDriver
//  extends ezSQL_mySQL
// global $db;

$conf->load('db');
if ($conf->db_type != 'mysql') // who knows?
	return;

require_once 'db/ez_sql_core.php';
require_once 'db/ez_sql_mysql.php';

final class HeliumDatabaseDriver extends ezSQL_mySQL {
	const all = '1';

    private static $instance;

    public function __construct() {
        parent::ezSQL_mySQL();
    }

    public function connect($dbuser = '', $dbpassword = '', $dbhost = 'localhost') {
		global $conf;

        $dbuser = $this->dbuser = $conf->db_user;
        $dbpassword = $this->dbpassword = $conf->db_pass;
        $dbname = $this->dbname = $conf->db_name;
        $dbhost = $this->dbhost = $conf->db_host;

        parent::connect($dbuser, $dbpassword, $dbhost);
        parent::select($dbname);
    }

    public function escape($string = false) {
        $this->connect();
        if ($string)
            return mysql_real_escape_string($string);
    }

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

        $db = $this ? $this : Helium::db();
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
