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

class Identifier extends Expression
{
	/**
	 * Return the quoted identifier.
	 *
	 * @return  mixed  expression
	 */
	public function getValue(Connection $connection)
	{
		return $connection->quoteIdentifier($this->value);
	}
}