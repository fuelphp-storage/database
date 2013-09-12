<?php
/**
 * Fuel\Database is an easy flexible PHP 5.3+ Database Abstraction Layer
 *
 * @package    Fuel\Database
 * @version    1.0
 * @author     Frank de Jonge
 * @license    MIT License
 * @copyright  2011 - 2012 FuelPHP Development Team
 */

namespace Fuel\Database\Collector;

use Fuel\Database\DB;
use Fuel\Database\Expression\Increment;

class Update extends Where
{
	/**
	 * @var  string  $type  query type
	 */
	public $type = DB::UPDATE;

	/**
	 * Constructor, sets the table name
	 */
	public function __construct($table = null)
	{
		$table and $this->table = $table;
	}

	/**
	 * Sets the table to update
	 *
	 * @param   string  $table  table to update
	 * @return  $this
	 */
	public function table($table)
	{
		$this->table = $table;

		return $this;
	}

	/**
	 * Set the new values
	 *
	 * @param   mixed   $key    string field name or associative values array
	 * @param   mixed   $value  new value
	 * @return  $this
	 */
	public function set($key, $value = null)
	{
		is_array($key) or $key = array($key => $value);

		foreach ($key as $k => $v)
		{
			$this->values[$k] = $v;
		}

		return $this;
	}

	/**
	 * Adds an increment statement
	 *
	 * @param   mixed   $key     string field name or associative values array
	 * @param   mixed   $amount  amount to increment by
	 * @return  $this
	 */
	public function increment($key, $amount = 1)
	{
		$statement = new Increment($key);
		$statement->amount($amount);

		$this->values[$key] = $statement;

		return $this;
	}
}
