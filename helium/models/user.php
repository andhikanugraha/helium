<?php

class User extends HeliumDBRecord {
	public $username;
	public $password_string;
	public $password_hash;

	public static function find() {
		$args = func_get_args();
		return parent::__find(__CLASS__, $args);
	}
	
	public static function salt() {
		return '';
		$salt = Helium::conf('salt');
		if (!$salt)
			$salt = Helium::$app_id;
	}

	public static function make_password_hash($password_string) {
		$salted = self::salt() . $password_string;
		return sha1($salted);
	}
	
	public static function validate($username, $password_string) {
		$password_hash = self::make_password_hash($password_string);
		$return = self::find(array('username' => $username, 'password_hash' => $password_hash));
		if (is_array($return))
			return $return[0];
		else
			return false;
	}
	
	public function save() {
		if ($this->password_string)
			$this->password_hash = self::make_password_hash($this->password_string);
		$this->__save();
	}
}

?>