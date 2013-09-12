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

namespace Fuel\Database;

abstract class Collector extends Query
{
	/**
	 * Retrieve the sql query
	 *
	 * @param   Fuel\Database\Compiler   $compiler  compiler
	 * @return  string                      sql query
	 * @throws  Fuel\Database\Exception  when there is no compiler
	 */
	public function getQuery(Compiler $compiler = null)
	{
		if ( ! $compiler and ! $compiler = $this->compiler)
		{
			throw new Exception('Query building needs a compiler to generage SQL.');
		}

		return $compiler->compile($this);
	}
}