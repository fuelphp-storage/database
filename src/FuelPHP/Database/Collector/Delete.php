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

use FuelPHP\Database\DB;

class Delete extends Where
{
	public $type = DB::DELETE;

	/**
	 * Constructor
	 *
	 * @param   string  $table  table name
	 */
	public function __construct($table = null)
	{
		$table and $this->table = $table;
	}

	/**
	 * Sets the table to update
	 *
	 * @param   string  $table  table to update
	 * @return  object  $this
	 */
	public function from($table)
	{
		$this->table = $table;

		return $this;
	}
}
