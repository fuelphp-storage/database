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

use Closure;
use Doctrine\DBAL\Schema\Comparator as DoctrineComparator;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;

class Schema
{
	/**
	 * @var  FuelPHP\Database\Connection  $connection  connection
	 */
	protected $connection;

	/**
	 * Constructor
	 *
	 * @param  Connection  $connection  connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function getPlatform()
	{
		return $this->connection->getDoctrineSchema()->getDatabasePlatform();
	}

	public function getComparator()
	{
		return new DoctrineComparator;

		if ( ! $this->comparator)
		{
			$this->comparator = new DoctrineComparator;
		}

		return $this->comparator;
	}

	public function getSchema()
	{
		return $this->connection->getDoctrineSchema()->createSchema();
	}

	public function alterTable($table, Closure $config)
	{
		$schema = $this->getSchema();
		$newSchema = clone $schema;
		$table = $newSchema->getTable($table);
		$config($table);
		$diff = $this->getComparator()->compare($schema, $newSchema);

		return (array) $diff->toSql($this->getPlatform());
	}
}