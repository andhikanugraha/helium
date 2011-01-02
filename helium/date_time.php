<?php

// HeliumDateTime
// A wrapper class for DateTime with additional features:
// * year, month, date, hour, minute(s), second(s) properties
// * __toString() for string typecasting returns the mysql formatted string.
// * a mysql_datetime() property

class HeliumDateTime extends DateTime {
	const MYSQL = 'Y-m-d H:i:s';

	public function mysql_datetime() {
		return $this->format(self::MYSQL);
	}

	public function __toString() {
		return $this->mysql_datetime();
	}

	public function __get($name) {
		switch ($name) {
			case 'year':
				return (int) $this->format('Y');
			case 'month':
				return (int) $this->format('m');
			case 'day':
				return (int) $this->format('d');
			case 'hour':
				return (int) $this->format('H');
			case 'minutes':
			case 'minute':
				return (int) $this->format('i');
			case 'seconds':
			case 'second':
				return (int) $this->format('s');
			default:
				return (int) $this->format($name);
		}
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'year':
				return $this->setDate($value, $this->month, $this->day);
			case 'month':
				return $this->setDate($this->year, $value, $this->day);
			case 'day':
				return $this->setDate($this->year, $this->month, $value);
			case 'hour':
				return $this->setTime($value, $this->minute, $this->second);
			case 'minutes':
			case 'minute':
				return $this->setTime($this->hour, $value, $this->second);
			case 'seconds':
			case 'second':
				return $this->setTime($this->hour, $this->minute, $value);
		}
	}
}