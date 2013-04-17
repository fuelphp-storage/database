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

class Join
{
	/**
	 * @var  string  $table  table to join
	 */
	public $table;

	/**
	 * @var  string  $type  join type
	 */
	public $type;

	/**
	 * @var  array  $on  array of on statements
	 */
	public $on = array();

	/**
	 * Join Contructor.
	 *
	 * @param  string  $table  table name
	 * @param  string  $type   type of join
	 */
	public function __construct($table, $type = null)
	{
		$this->table = $table;
		$this->type = $type;
	}

	/**
	 * Adds an 'on' clause for the join.
	 *
	 * @param   string|array  $column  string column name or array for alias
	 * @param   string        $op      logic operator
	 * @param   string|array  $value   value or array for alias
	 */
	public function on($column, $op, $value = null)
	{
		if (func_num_args() === 2)
		{
			$value = $op;
			$op = '=';
		}

		$this->on[] = array($column, $op, $value, 'AND');
	}

	/**
	 * Adds an 'on' clause for the join.
	 *
	 * @param   string|array  $column  string column name or array for alias
	 * @param   string        $op      logic operator
	 * @param   string|array  $value   value or array for alias
	 */
	public function andOn($column, $op, $value = null)
	{
		call_user_func_array(array($this, 'on'), func_get_args());
	}


	/**
	 * Adds an 'on' clause for the join.
	 *
	 * @param   string|array  $column  string column name or array for alias
	 * @param   string        $op      logic operator
	 * @param   string|array  $value   value or array for alias
	 */
	public function orOn($column, $op, $value = null)
	{
		if (func_num_args() === 2)
		{
			$value = $op;
			$op = '=';
		}

		$this->on[] = array($column, $op, $value, 'OR');
	}
}
