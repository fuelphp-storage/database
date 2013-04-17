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

namespace FuelPHP\Database;

class Expression
{
	/**
	 * @var  mixed  $value  expression
	 */
	public $value;

	/**
	 * Constructor
	 *
	 * @param  mixed  $value  expression
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * Return the expression.
	 *
	 * @return  mixed  expression
	 */
	public function getValue(Connection $connetion)
	{
		return $this->value;
	}
}