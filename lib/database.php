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
