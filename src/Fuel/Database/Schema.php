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

use Closure;
use Doctrine\DBAL\Schema\Comparator as DoctrineComparator;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;

class Schema
{
	/**
	 * @var  Fuel\Database\Connection  $connection  connection
	 */
	protected $connection;

	protected $platform;

	protected $manager;

	protected $schema;

	/**
	 * Constructor
	 *
	 * @param  Connection  $connection  connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Get the Doctrine Platform instance
	 *
	 * @return  Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	public function getPlatform()
	{
		if ( ! $this->platform)
		{
			$this->platform = $this->getSchemaManager()->getDatabasePlatform();
		}

		return $this->platform;
	}

	/**
	 * Get the Doctrine Schema Manager
	 *
	 * @return  Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	public function getSchemaManager()
	{
		if ( ! $this->manager)
		{
			$this->manager = $this->connection->getSchemaManager();
		}
		return $this->manager;
	}

	/**
	 * Get the Doctrine Schema
	 *
	 * @return  Doctrine\DBAL\Schema\Schema
	 */
	public function getSchema()
	{
		if ( ! $this->schema)
		{
			$this->schema = $this->getSchemaManager()->createSchema();
		}

		return $this->schema;
	}

	public function getTable($table)
	{
		$schema = $this->getSchema();

		return new Schema\Table($schema->getTable($table));
	}

	public function getColumn($table, $column)
	{
		return $this->getTable($table)->getColumn($column);
	}

	/**
	 * Alter a table
	 *
	 * @param   string   $table   table name
	 * @param   Closure  $config  configuration callback
	 */
	public function alterTable($table, Closure $config)
	{
		$schema = $this->getSchema();
		$newSchema = clone $schema;
		$table = new Schema\Table($newSchema->getTable($table), $schema);
		$config($table);
		$comparator = new DoctrineComparator;
		$diff = $comparator->compare($schema, $newSchema);
		$commands = (array) $diff->toSql($this->getPlatform());
		$this->runCommands($commands);
	}

	/**
	 * Create a table
	 *
	 * @param   string   $table   table name
	 * @param   Closure  $config  configuration callback
	 */
	public function createTable($table, Closure $config)
	{
		$schema = new DoctrineSchema;
		$table = new Schema\Table($schema->createTable($table), $schema);
		$config($table);
		$commands = (array) $schema->toSql($this->getPlatform());
		$this->runCommands($commands);
	}

	/**
	 * Drop a table
	 *
	 * @param   string   $table   table name
	 */
	public function dropTable($table)
	{
		$schema = $this->getSchema();
		$old = clone $schema;
		$table = $schema->dropTable($table);
		$comparator = new DoctrineComparator;
		$diff = $comparator->compare($old, $schema);
		$commands = (array) $diff->toSql($this->getPlatform());
		$this->runCommands($commands);
	}

	public function hasTable($table)
	{
		$tables = $this->listTableNames();

		return in_array($table, $tables);
	}

	public function listTableNames()
	{
		return $this->getSchemaManager()->listTableNames();
	}

	public function hasColumn($table, $column)
	{
		$columns = $this->listColumnNames($table);

		return in_array($column, $columns);
	}

	public function listColumnNames($table)
	{
		return array_map(function($column)
		{
			return $column->getName();
		},
		$this->listColumns($table));
	}

	public function listColumns($table)
	{
		return $this->getSchemaManager()->listTableColumns($table);
	}

	public function runCommands(array $commands)
	{
		foreach ($commands as $command)
		{
			$this->connection->execute(DB::PLAIN, $command);
		}

		$this->schema = null;
	}

	/**
	 * Get table details
	 *
	 * @param   string  $table  table name
	 * @return  Doctrine\DBAL\Schema\Table
	 */
	public function tableDetails($table)
	{
		$schema = $this->getSchemaManager();

		return $schema->listTableDetails($table);
	}
}