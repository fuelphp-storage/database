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
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;

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
		return $this->connection
			->getDoctrineSchema()
			->getDatabasePlatform();
	}

	public function getSchema()
	{
		return $this->connection
			->getDoctrineSchema()
			->createSchema();
	}

	public function alterTable($table, Closure $config)
	{
		$schema = $this->getSchema();
		$newSchema = clone $schema;
		$table = new Schema\Table($newSchema->getTable($table), $schema);
		$config($table);
		$comparator = new DoctrineComparator;
		$diff = $comparator->compare($schema, $newSchema);

		return (array) $diff->toSql($this->getPlatform());
	}

	public function renameTable($from, $to)
	{
		$schema = $this->getSchema();
		$from = $schema->getTable($from);
		//$to =
	}



	public function createTable($table, Closure $config)
	{
		$schema = new DoctrineSchema;
		$table = new Schema\Table($schema->createTable($table), $schema);
		$config($table);

		return (array) $schema->toSql($this->getPlatform());
	}

	public function dropTable($table)
	{
		$schema = $this->getSchema();
		$old = clone $schema;
		$table = $schema->dropTable($table);
		$comparator = new DoctrineComparator;
		$diff = $comparator->compare($old, $schema);

		return (array) $diff->toSql($this->getPlatform());
	}
}