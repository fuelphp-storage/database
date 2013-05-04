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

namespace FuelPHP\Database\Expression;

use FuelPHP\Database\Expression;
use FuelPHP\Database\Connection;

class Command extends Expression
{
	/**
	 * @var  $command  string  command name
	 */
	public $command;

	/**
	 * @var  array  $arguments  command arguments
	 */
	public $arguments;

	/**
	 * @var  string  $alias  alias
	 */
	public $alias;

	/**
	 * Constructor
	 *
	 * @param   string  $value  command name
	 */
	public function __construct($value)
	{
		$arguments = func_get_args();
		$this->command = array_shift($arguments);
		$this->arguments = $arguments;
	}

	/**
	 * Set the return value alias
	 *
	 * @param   string  $alias  alias
	 * @return  $this
	 */
	public function alias($alias)
	{
		$this->alias = $alias;

		return $this;
	}

	/**
	 * Format the concat statement
	 *
	 * @param    FuelPHP\Database\Connection  $connection
	 * @return   string  concat statement
	 */
	public function compileConcat($connection)
	{
		$compiler = $connection->getCompiler();

		if (method_exists($compiler, $method = 'compileCommand'.ucfirst($this->command)))
		{
			return call_user_func_array(array($compiler, $method), $this->arguments);
		}

		$command = strtoupper($this->command);
		$arguments = empty($this->arguments) ? '' : $connection->quote($this->arguments);

		return $command .'('.$arguments.')';
	}

	/**
	 * Compiles the command.
	 *
	 * @param   FuelPHP\Database\Connection  $connection
	 * @return  string                       command
	 */
	public function getValue(Connection $connection)
	{
		$statement = $this->compileConcat($connection);

		if ($this->alias)
		{
			return $statement.' AS '.$this->alias;
		}

		return $statement;
	}
}