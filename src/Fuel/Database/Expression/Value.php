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

namespace Fuel\Database\Expression;

use Fuel\Database\Expression;
use Fuel\Database\Connection;

class Value extends Expression
{
	/**
	 * Return the quoted identifier.
	 *
	 * @return  mixed  expression
	 */
	public function getValue(Connection $connection)
	{
		return $connection->quote($this->value);
	}
}