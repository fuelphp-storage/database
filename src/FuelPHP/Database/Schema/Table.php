<?php

namespace FuelPHP\Database\Schema;

use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;

class Table
{
	protected $table;

	protected $schema;

	public function __construct(DoctrineTable $table, DoctrineSchema $schema)
	{
		$this->table = $table;
		$this->schema = $schema;
	}

	public function string($name, $length = null, $default = null)
	{
		return new Column($this->table->addColumn($name, 'string', array(
			'length' => $length,
			'default' => $default,
		)), $this);
	}

	public function integer($name, $length = null, $default = null)
	{
		return new Column($this->table->addColumn($name, 'integer', array(
			'length' => $length,
			'default' => $default,
		)), $this);
	}

	public function drop($column)
	{
		$this->table->dropColumn($column);

		return $this;
	}

	public function boolean($name, $default = false)
	{
		return new Column($this->table->addColumn($name, 'boolean', array(
			'default' => $default,
		)), $this);
	}

	public function change($name)
	{
		return new Column($this->table->getColumn($name), $this);
	}

	public function copy($column, $as)
	{
		$column = $this->table->getColumn($column);

		return new Column($this->table->addColumn($as, strtolower($column->getType()), $column->toArray()), $this);
	}

	public function rename($from, $to)
	{
		$this->copy($from, $to);

		return $this->drop($from);
	}

	public function engine($engine)
	{
		$this->table->addOption('engine', $engine);

		return $this;
	}

	public function charset($charset)
	{
		$this->table->addOption('charset', $charset);

		return $this;
	}

	public function collate($collate)
	{
		$this->table->addOption('collate', $collate);

		return $this;
	}

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