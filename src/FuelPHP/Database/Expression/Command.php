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
	 * Constructor
	 *
	 * @param   string  $value  command name
	 */
	public function __construct($value)
	{
		$arguments = func_get_args();
		$this->command = array_pop($arguments);
		$this->arguments = $arguments;
	}

	/**
	 * Compiles the command.
	 *
	 * @param   FuelPHP\Database\Connection  $connection
	 * @return  string                       command
	 */
	public function getValue(Connection $connetion)
	{
		$command = strtoupper($this->command);
		$arguments = empty($this->arguments) ? '' : $connetion->quote($this->arguments);

		return $command .'('.$arguments.')';
	}
}