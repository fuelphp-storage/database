<?php

namespace FuelPHP\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;

class Column
{
	protected $column;

	protected $table;

	public function __construct(DoctrineColumn $column, Table $table)
	{
		$this->column = $column;
	}

	public function nullable($null = true)
	{
		$this->column->setNotnull( ! $null);

		return $this;
	}

	public function null($null = true)
	{
		return $this->nullable($null);
	}

	public function increment($increment = true)
	{
		$this->column->setAutoincrement($increment);

		return $this;
	}

	public function primary()
	{
		$column = (array) $this->column->getName();
		$this->table->setPrimaryKey($column);

		return $this;
	}

	public function unique()
	{
		$column = (array) $this->column->getName();
		$this->table->addUniqueIndex($column);

		return $this;
	}

	public function __call($method, $arguments)
	{
		if ( ! method_exists($this->column, $method) and ! method_exists($this->column, $method = 'set'.ucfirst($method)))
		{
			throw new \BadMethodCallException('Call to undefined function '.get_class($this).'::'.$method);
		}

		$result = call_user_func_array(array($this->column, $method), $arguments);

		// Ensure chaining for the table
		if ($result instanceof DoctrineColumn)
		{
			return $this;
		}

		return $result;
	}

}