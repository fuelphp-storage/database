<?php

namespace FuelPHP\Database\Schema;

use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;

class Table
{
	/**
	 * @var  \Doctrine\DBAL\Schema\Table  $table
	 */
	protected $table;

	/**
	 * Constructor
	 *
	 * @param   \Doctrine\DBAL\Schema\Table  $table  Doctrine table object
	 */
	public function __construct(DoctrineTable $table)
	{
		$this->table = $table;
	}

	/**
	 * Add a column
	 *
	 * @param   string  $name     column name
	 * @param   string  $type     column type
	 * @param   array   $options  column options
	 * @return  \FuelPHP\Database\Schema\Column  wrapped column
	 */
	public function add($name, $type, array $options)
	{
		$column = $this->table->addColumn($name, $type, $options);

		return new Column($column, $this);
	}

	public function string($name, $length = null, $default = null)
	{
		return $this->add($name, 'string', compact('length', 'default'));
	}

	public function integer($name, $length = null, $default = null)
	{
		return $this->add($name, 'integer', compact('length', 'default'));
	}

	public function increment($name, $length = null)
	{
		return $this->integer($name, $length)->increment();
	}

	public function drop($column)
	{
		$this->table->dropColumn($column);

		return $this;
	}

	public function text($name, $default = null, $length = 65532)
	{
		return $this->add($name, 'text', compact('default', 'length'));
	}

	public function boolean($name, $default = false)
	{
		return $this->add($name, 'boolean', compact('default'));
	}

	public function bool($name, $default = false)
	{
		return $this->boolean($name, $default);
	}

	public function decimal($name, $precision, $scale, $default = null)
	{
		return $this->add($name, 'decimal', compact('precision', 'scale', 'default'));
	}

	public function float($name, $precision, $scale, $default = null)
	{
		return $this->add($name, 'float', compact('precision', 'scale', 'default'));
	}

	/**
	 * Return a column to be modified
	 *
	 * @param   string  $name  column name
	 * @return  \FuelPHP\Database\Schema\Column  wrapped column
	 */
	public function change($name)
	{
		return new Column($this->table->getColumn($name), $this);
	}

	/**
	 * Copy and add a column
	 *
	 * @param   string  $name  column name
	 * @param   string  $as    new column name
	 * @return  \FuelPHP\Database\Schema\Column  wrapped column
	 */
	public function copy($column, $as)
	{
		$column = $this->table->getColumn($column);
		$new = $this->table->addColumn($as, strtolower($column->getType()), $column->toArray());

		return new Column($new, $this);
	}

	/**
	 * Copy and drop the old column.
	 *
	 * @param   string  $from  column to copy
	 * @param   string  $to    new column name
	 * @return  \FuelPHP\Database\Schema\Column  wrapped column
	 */
	public function rename($from, $to)
	{
		$this->copy($from, $to);

		return $this->drop($from);
	}

	/**
	 * Sets the engine option
	 *
	 * @param   string  $engine  engine
	 * @return  \FuelPHP\Database\Schema\Table  table
	 */
	public function engine($engine)
	{
		$this->table->addOption('engine', $engine);

		return $this;
	}

	/**
	 * Sets the charset option
	 *
	 * @param   string  $charset  charset
	 * @return  \FuelPHP\Database\Schema\Table  table
	 */
	public function charset($charset)
	{
		$this->table->addOption('charset', $charset);

		return $this;
	}

	/**
	 * Sets the collate option
	 *
	 * @param   string  $collate  collate
	 * @return  \FuelPHP\Database\Schema\Table  table
	 */
	public function collate($collate)
	{
		$this->table->addOption('collate', $collate);

		return $this;
	}

	/**
	 * Adds an index
	 *
	 * @param   string  $fields  fields
	 * @param   string  $name    index name
	 * @return  \FuelPHP\Database\Schema\Table  table
	 */
	public function index($fields, $name = null)
	{
		$fields = (array) $fields;

		$this->table->addIndex($fields, $name);

		return $this;
	}

	/**
	 * Drop an index
	 *
	 * @param   string  $name  index name
	 * @return  \FuelPHP\Database\Schema\Table  table
	 */
	public function dropIndex($name)
	{
		$this->table->dropIndex($name);

		return $this;
	}

	/**
	 * Adds a unique index
	 *
	 * @param   string  $fields  fields
	 * @param   string  $name    index name
	 * @return  \FuelPHP\Database\Schema\Table  table
	 */
	public function unique($fields, $name)
	{
		$this->table->addUniqueIndex((array) $fields, $name);

		return $this;
	}

	public function primary($fields)
	{
		$this->table->setPrimaryKey((array) $fields);

		return $this;
	}

	/**
	 * __toString
	 *
	 * @return  string  table name
	 */
	public function __toString()
	{
		return $this->table->getName();
	}

	/**
	 * Call fallthrough to Doctrine table object.
	 *
	 * @param   string  $method     method name
	 * @param   array   $arguments  method arguments
	 * @return  mixed   call result
	 */
	public function __call($method, $arguments)
	{
		if ( ! method_exists($this->table, $method) and ! method_exists($this->table, $method = 'set'.ucfirst($method)))
		{
			throw new \BadMethodCallException('Call to undefined function '.get_class($this).'::'.$method);
		}

		$result = call_user_func_array(array($this->table, $method), $arguments);

		// Ensure chaining for the table
		if ($result instanceof DoctrineTable)
		{
			return $this;
		}

		// Construct a column wrapper for retrieved columns
		elseif ($result instanceof DoctrineColumn)
		{
			return new Column($result, $this);
		}

		return $result;
	}
}