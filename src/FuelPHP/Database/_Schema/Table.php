<?php
/**
 * FuelPHP\Database is an easy flexible PHP 5.3+ Database Abstraction Layer
 *
 * @package    FuelPHP\Database
 * @version    1.0
 * @author     Frank de Jonge
 * @license    MIT License
 * @copyright  2011 - 2012 FuelPHP Development Team
 */

namespace FuelPHP\Database\Schema;

use FuelPHP\Databse\Connection;

class Table extends Collector
{
	/**
	 * @var  string  $table  table name
	 */
	public $table;

	/**
	 * @var  array  $fields  fields
	 */
	public $fields = array();

	/**
	 * @var  array  $indexes  indexes
	 */
	public $indexes = array();

	/**
	 * Constructor
	 *
	 * @param  string   $table   table name
	 * @param  Closure  $config  configuration closure
	 */
	public function __construct($table, Closure $config = null)
	{
		$this->table = $table;
		$config and $config($this);
	}

	public function addField($name, $type, $default = null)
	{
		$field = new Field($name, $type, $default);
		$this->fields[] = $field;

		return $field;
	}

	public function increment($name, $length = 11)
	{
		return $this->addField($name, 'integer')
			->length($length)
			->autoIncrement(true);
	}

	public function varchar($name, $length, $default = null)
	{
		return $this->addField($name, 'varchar', $default)
			->length($length);
	}

	public function integer($name, $length, $default = null)
	{
		return $this->addField($name, 'integer', $default)->length($length);
	}

	public function enum($name, $options, $default = null)
	{
		return $this->addField($name, 'enum', $default)->options($options);
	}

	public function decimal($name, $length, $scale, $default = null)
	{
		return $this->addField($name, 'decimal', $default)->length($length)->scale($scale);
	}

	public function float($name, $length, $scale, $default = null)
	{
		return $this->addField($name, 'float', $default)->length($length)->scale($scale);
	}

	public function boolean($name, $default = null)
	{
		return $this->addField($name, 'boolean', $default);
	}

	public function timestamp($name, $default = 0)
	{
		return $this->addField($name, 'timestamp', $default);
	}

	public function text($name, $default = null)
	{
		return $this->addField($name, 'text', $default);
	}

	public function textarea($name, $default = null)
	{
		return $this->text($name, $default);
	}

	public function index($type, $fields, $name = null)
	{
		if ( ! $name)
		{
			$name = static::indexName($fields, $type);
		}

		$action = 'add';
		$this->indexes[] = compact('type', 'fields', 'name', 'action');

		return $this;
	}

	public function primary($fields, $name = null)
	{
		return $this->index('primary', $fields, $name);
	}

	public function unique($fields, $name = null)
	{
		return $this->index('unique', $fields, $name);
	}

	public function fulltext($fields, $name = null)
	{
		return $this->index('fulltext', $fields, $name);
	}

	public function dropIndex($index, $type = null)
	{
		$action = 'drop';
		$name = static::indexName($index, $type);
		$this->indexes[] = compact('type', 'name', 'action');

		return $this;
	}

	public static function indexName($index, $type = null)
	{
		if (is_array($index))
		{
			$index = implode('_', $index);
		}

		if ($type)
		{
			$index .= '_'.$type;
		}

		return $index;
	}
}