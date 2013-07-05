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
	public $table;

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

	/**
	 * Add a string column
	 *
	 * @param   string   $name     column name
	 * @param   integer  $length   length
	 * @param   string   $default  default value
	 * @return  Column  wrapped column
	 */
	public function string($name, $length = null, $default = null)
	{
		return $this->add($name, 'string', compact('length', 'default'));
	}

	/**
	 * Add an integer column
	 *
	 * @param   string   $name     column name
	 * @param   integer  $length   length
	 * @param   integer  $default  default value
	 * @return  Column  wrapped column
	 */
	public function integer($name, $length = null, $default = null)
	{
		return $this->add($name, 'integer', compact('length', 'default'));
	}

	/**
	 * Add an incremental column
	 *
	 * @param   string   $name     column name
	 * @param   integer  $length   length
	 * @return  Column  wrapped column
	 */
	public function increment($name, $length = null)
	{
		return $this->integer($name, $length)->increment();
	}

	/**
	 * Drop a column
	 *
	 * @param   string   $name     column name
	 * @return  $this
	 */
	public function drop($column)
	{
		$this->table->dropColumn($column);

		return $this;
	}

	/**
	 * Add an text column
	 *
	 * @param   string   $name     column name
	 * @param   string   $default  default
	 * @param   integer  $length   length
	 * @return  Column  wrapped column
	 */
	public function text($name, $default = null, $length = 65532)
	{
		return $this->add($name, 'text', compact('default', 'length'));
	}

	/**
	 * Add an boolean column
	 *
	 * @param   string   $name     column name
	 * @param   boolean  $default  default
	 * @return  Column  wrapped column
	 */
	public function boolean($name, $default = false)
	{
		return $this->add($name, 'boolean', compact('default'));
	}

	/**
	 * Add an boolean column
	 *
	 * @param   string   $name     column name
	 * @param   boolean  $default  default
	 * @return  Column  wrapped column
	 */
	public function bool($name, $default = false)
	{
		return $this->boolean($name, $default);
	}

	/**
	 * Add an decimal column
	 *
	 * @param   string   $name       column name
	 * @param   string   $precision  precision
	 * @param   string   $scale      scale
	 * @param   decimal  $default    default
	 * @return  Column  wrapped column
	 */
	public function decimal($name, $precision, $scale, $default = null)
	{
		return $this->add($name, 'decimal', compact('precision', 'scale', 'default'));
	}

	/**
	 * Add an float column
	 *
	 * @param   string   $name       column name
	 * @param   string   $precision  precision
	 * @param   string   $scale      scale
	 * @param   decimal  $default    default
	 * @return  Column  wrapped column
	 */
	public function float($name, $precision, $scale, $default = null)
	{
		return $this->add($name, 'float', compact('precision', 'scale', 'default'));
	}

	/**
	 * Return a column to be modified
	 *
	 * @param   string  $name  column name
	 * @return  Column  wrapped column
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
	 * @return  Column  wrapped column
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
	 * @return  $this
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
	 * @return  $this
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
	 * @return  $this
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
	 * @return  $this
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
	 * @return  $this
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
	 * @return  $this
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
	 * @return  $this
	 */
	public function unique($fields, $name)
	{
		$this->table->addUniqueIndex((array) $fields, $name);

		return $this;
	}

	/**
	 * Adds a fulltext index to the table
	 *
	 * @param   array   $fields  fields
	 * @param   string  $name    index name
	 * @return  $this
	 */
	public function fulltext($fields, $name)
	{
		$this->table->addIndex((array) $fields, $name);
		$index = $this->table->getIndex($name);
		$index->addFlag('fulltext');

		return $this;
	}

	/**
	 * Sets the primary field(s)
	 *
	 * @param   array|string  fieldname(s)
	 * @return  $this
	 */
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