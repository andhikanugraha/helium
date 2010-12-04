<?php

// HeliumDB
// extends ezSQL_mySQL
// Helium::db();

require_once 'db/ez_sql_core.php';
require_once 'db/ez_sql_mysql.php';

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
}
