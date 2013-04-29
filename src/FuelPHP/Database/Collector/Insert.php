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

namespace FuelPHP\Database\Collector;

use FuelPHP\Database\Collector;
use FuelPHP\Database\DB;

class Insert extends Collector
{
	/**
	 * @var  string  $type  query type
	 */
	public $type = DB::INSERT;

	/**
	 * @var  array  $columns  columns to use
	 */
	public $columns = array();

	/**
	 * @var  array  $values  values for insert
	 */
	public $values = array();

	/**
	 * Constructor
	 *
	 * @param  string  $table  table name
	 */
	public function __construct($table)
	{
		$this->into($table);
	}

	/**
	 * Sets the table to insert into.
	 *
	 * @param   string  $table  table to insert into
	 * @return  $this
	 */
	public function into($table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Adds values to insert
	 *
	 * @param   array   $values  array or collection of arrays to insert
	 * @param   bool    $merge   wether to merge the values with the last inserted set
	 * @return  $this
	 */
	public function values(array $values, $merge = false)
	{
		is_array(reset($values)) or $values = array($values);

		foreach($values as $v)
		{
			$keys = array_keys($v);
			$this->columns = array_merge($this->columns, $keys);

			if($merge and count($this->values))
			{
				$last = array_pop($this->values);
				$this->values[] = array_merge($last, $v);
			}
			else
			{
				$this->values[] = $v;
			}
		}

		return $this;
	}
}
