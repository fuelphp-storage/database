<?php

namespace Fuel\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Types\Type;

class Column
{
	/**
	 * @var  \Doctrine\DBAL\Schema\Column  $column  doctrine column object
	 */
	public $column;

	/**
	 * @var  \Fuel\Database\Schema\Table  $table  table
	 */
	public $table;

	/**
	 * Constructor
	 *
	 * @param  \Doctrine\DBAL\Schema\Column    $column  doctrine column object
	 * @param  \Fuel\Database\Schema\Table  $table   table object
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
		$column = $this->column->getName();

		$this->table->primary($column);

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

		$this->table->unique($columns, $name);

		return $this;
	}

	/**
	 * Adds the column as a unique index
	 *
	 * @param   string  $name  index name
	 * @return  $this
	 */
	public function index($name = null)
	{
		$column = $this->column->getName();

		if ( ! $name)
		{
			$name = $column.'_index';
		}

		$columns = (array) $column;

		$this->table->index($columns, $name);

		return $this;
	}

	/**
	 * Set the type of a column.
	 *
	 * @param   mixed  $type
	 * @return  $this
	 */
	public function setType($type)
	{
		if (is_string($type)) {
			$type = Type::getType($type);
		}

		$this->column->setType($type);

		return $this;
	}

	/**
	 * Set the type of a column.
	 *
	 * @param   mixed  $type
	 * @return  $this
	 */
	public function getType()
	{
		$type = $this->column->getType();

		return $type->getName();
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
			throw new \BadMethodCallException('Call to undefined function '.get_class($this).'::'.func_get_arg(0));
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