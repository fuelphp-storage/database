<?php

namespace FuelPHP\Database\Schema;

class Field extends Collector
{
	/**
	 * @var  string  $field  field name
	 */
	public $field;

	/**
	 * @var  string  $type  field type
	 */
	public $type;

	/**
	 * Constructor
	 *
	 * @param  string  $field     field field
	 * @param  string  $field     field field
	 * @param  string  $default  field default
	 */
	public function __construct($field, $type = null, $default = null)
	{
		$this->field = $field;
		$this->type = $type;
		if ($default)
		{
			$this->default = $default;
		}
	}
}