<?php

namespace FuelPHP\Database\Schema;

class Database extends Collector
{
	public $database;

	public function __construct($database)
	{
		$this->database = $database;
	}
}