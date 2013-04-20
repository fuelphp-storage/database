<?php

namespace FuelPHP\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;

class Column
{
	/**
	 * @var  \Doctrine\DBAL\Schema\Column  $column  doctrine column object
	 */
	protected $column;

	/**
	 * @var  \FuelPHP\Database\Schema\Table  $table  table
	 */
	protected $table;

	/**
	 * Constructor
	 *
	 * @param  \Doctrine\DBAL\Schema\Column    $column  doctrine column object
	 * @param  \FuelPHP\Database\Schema\Table  $table   table object
	 */
	public function __construct(DoctrineColumn $column, Table $table)
	{
		$this->column = $column;
		$this->table = $table;
	}

	/**
	 * Sets the nullable option
	 *
	 * @param   boolean  $null  null setting
	 * @return  $this
	 */
	public function nullable($null = true)
	{
		$this->column->setNotnull( ! $null);

		return $this;
	}

	/**
	 * Sets the nullable option
	 *
	 * @param   boolean  $null  null setting
	 * @return  $this
	 */
	public function null($null = true)
	{
		return $this->nullable($null);
	}

	/**
	 * Sets the auto increment option
	 *
	 * @param   boolean  $inctement  inctement setting
	 * @return  $this
	 */
	public function increment($increment = true)
	{
		$this->column->setAutoincrement($increment);

		return $this;
	}

	/**
	 * Adds the column as a primary key
	 *
	 * @return  $this
	 */
	public function primary()
	{
		$column = (array) $this->column->getName();
		$this->table->setPrimaryKey($column);

		return $this;
	}

	/**
	 * Adds the column as a unique index
	 *
	 * @param   string  $name  index name
	 * @return  $this
	 */
	public function unique($name = null)
	{
		$column = $this->column->getName();

		if ( ! $name)
		{
			$name = $column.'_unique';
		}
		$columns = (array) $column;

		$this->table->addUniqueIndex($columns, $name);

		return $this;
	}

	/**
	 * __toString
	 *
	 * @return  string  column name
	 */
	public function __toString()
	{
		return $this->column->getName();
	}

	/**
	 * Call fallthrough to Doctrine columns object.
	 *
	 * @param   string  $method     method name
	 * @param   array   $arguments  method arguments
	 * @return  mixed   call result
	 */
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