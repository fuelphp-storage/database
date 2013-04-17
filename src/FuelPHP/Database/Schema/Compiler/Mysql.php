<?php

namespace FuelPHP\Database\Schema\Compiler;

use FuelPHP\Database\Schema\Field;
use FuelPHP\Database\Schema\Table;
use FuelPHP\Database\Schema\Compiler;

class Mysql extends Compiler
{
	/**
	 * Compiles an integer field
	 *
	 * @param   \FuelPHP\Database\Field  $field  field
	 * @return  $this
	 */
	public function compileFieldInteger(Field $field)
	{
		$sql = $this->compileFieldDefault($field);

		if ($field->get('increment'))
		{
			$sql .= ' PRIMARY KEY';
		}

		return $sql;
	}

	/**
	 * Compiles an auto increment statement
	 *
	 * @param   \FuelPHP\Database\Field  $field  field
	 * @return  $this
	 */
	public function compileAutoIncrement(Field $field)
	{
		if ($field->get('increment'))
		{
			return 'AUTO_INCREMENT ';
		}
	}

	/**
	 * Compiles an engine clause
	 *
	 * @param   \FuelPHP\Database\Table  $table  table
	 * @return  $this
	 */
	public function compileEngine(Table $table)
	{
		if ($engine = $table->get('engine'))
		{
			return 'ENGINE = '.$engine.' ';
		}
	}

	public function compileListTables()
	{
		return 'SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'';
	}

	public function compileListFields($table)
	{
		return 'DESCRIBE '.$table;
	}

	public function normalizeFields(array $fields)
	{
		$defaults = $this->getFieldDefaults();

		return array_map(function($field) use($defaults)
		{
			$normalized = $defaults;
			$normalized['name'] = $field['field'];
			$normalized['type'] = $field['type'];
			$normalized['default'] = $field['default'];
			$normalized['null'] = stripos($field['null'], 'yes') === 0;
			$normalized['primary'] = stripos($field['key'], 'pri') !== false;
			$normalized['increment'] = stripos($field['extra'], 'auto_increment') !== false;

			if (strpos($normalized['type'], '('))
			{
				list($normalized['type'], $length) = explode('(', $field['type']);
				$normalized['length'] = (int) trim($length, ') ');
			}

			return $normalized;
		},
		$fields);
	}
}