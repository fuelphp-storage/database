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

namespace FuelPHP\Database\Connection;

use PDO;
use PDOStatement;
use FuelPHP\Database\Connection;

class Pgsql extends Connection
{
	protected $driver = 'pgsql';

	/**
	 * Retrieve the last insert id from an insert query
	 *
	 * @param   PDOStatement  $statement
	 * @param   array         $options
	 * @return  mixed
	 */
	public function getLastInsertId($statement, array $options)
	{
		if ( ! $field = $options['insertIdField'])
		{
			return null;
		}

		$result = $statement->fetch(PDO::FETCH_ASSOC);

		return $result[$field];
	}
}