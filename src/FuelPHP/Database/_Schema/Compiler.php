<?php

namespace FuelPHP\Database\Schema;

use BadMethodCallException;
use FuelPHP\Database\Connection;

abstract class Compiler
{
	/**
	 * @var  FuelPHP\Database\Connection  $connection connection
	 */
	protected $connection;

	/**
	 * @var  FuelPHP\Database\Compiler  $baseCompiler base compiler
	 */
	protected $baseCompiler;

	/**
	 * Constructor
	 *
	 * @param  FuelPHP\Database\Connection       $connection
	 * @param  FuelPHP\Database\Compiler\Schema  $compiler
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Compiles a create table query
	 *
	 * @param   \FuelPHP\Database\Table  $table  table
	 * @return  string                   querty
	 */
	public function compileCreateTable(Table $table)
	{
		$sql = 'CREATE TABLE ';

		if ($table->get('ifNotExists'))
		{
			$sql .= 'IF NOT EXISTS ';
		}

		$sql .= $this->quoteIdentifier($table->table) . ' ( ';
		$sql .= $this->compileFields($table->fields, 'create');
		$sql .= ' ) '.$this->compileEngine($table);
		$sql .= $this->compileComment($table, '= ');
		return $sql;
	}

	/**
	 * Compiles a drop fields query
	 *
	 * @param   \FuelPHP\Database\Table  $table  table
	 * @return  string                   querty
	 */
	public function compileDropFields(Table $table)
	{
		$sql = 'ALTER TABLE '.$this->quoteIdentifier($table->table);
		$fields = array_map(array($this, 'quoteIdentifier'), $table->fields);

		return $sql.' DROP '.implode(', ', $fields);
	}

	/**
	 * Compiles an alter table query
	 *
	 * @param   \FuelPHP\Database\Table  $table  table
	 * @return  string                   querty
	 */
	public function compileAlterTable(Table $table)
	{
		$sql = 'ALTER TABLE '.$this->quoteIdentifier($table->table).' ';
		$sql .= $this->compileFields($table->fields, 'alter');

		return $sql;
	}

	public function compileDropTable(Table $table)
	{
		$sql =  'DROP TABLE ';

		if ($table->get('ifExists'))
		{
			$sql .= 'IF EXISTS ';
		}

		return $sql.$this->quoteIdentifier($table->table);
	}

	public function compileRenameTable($table)
	{
		$sql = 'RENAME TABLE '.$this->quoteIdentifier($table->table).
			' TO '.$this->quoteIdentifier($table->get('name'));
	}

	public function compileFields(array $fields, $type)
	{
		$fieldCompiler = array($this, 'compileField');
		$types = array_fill(1, count($fields), $type);
		$fieldParts = array_map($fieldCompiler, $fields, $types);

		return implode(', ', $fieldParts);
	}

	public function compileField(Field $field, $type)
	{
		$sql = $this->compileFieldPrefix($field, $type);
		$sql .= $this->quoteIdentifier($field->field).' ';

		if ($name = $field->get('name'))
		{
			$sql .= $this->quoteIdentifier($name).' ';
		}

		$compiler = 'compileField'.ucfirst($field->type);

		if ( ! method_exists($this, $compiler))
		{
			$compiler = 'compileFieldDefault';
		}

		$sql .= $this->{$compiler}($field).' ';
		$sql .= $this->compileCharset($field);
		$sql .= $this->compileUnsigned($field);
		$sql .= $this->compileNullable($field);
		$sql .= $this->compileDefaultValue($field);
		$sql .= $this->compileAutoIncrement($field);
		$sql .= $this->compilePosition($field);
		$sql .= $this->compileComment($field);

		return trim($sql);
	}

	public function compileFieldPrefix(Field $field, $type)
	{
		if ($type === 'create')
		{
			return;
		}

		if (isset($field->name))
		{
			return 'CHANGE ';
		}

		return 'MODIFY ';
	}

	public function compileComment(Collector $collector)
	{
		if ($comment = $collector->get('comment') or $comment = $collector->get('comments'))
		{
			return 'COMMENT '.$this->quote($comment).' ';
		}
	}

	public function compileDefaultValue(Field $field)
	{
		if (isset($field->default))
		{
			$value = $field->get('default');

			return 'DEFAULT '.$this->quote($value). ' ';
		}
	}

	public function compileAutoIncrement(Field $field)
	{
		return '';
	}

	public function compilePosition(Field $field)
	{
		if ($field->get('first') === true)
		{
			return 'FIRST ';
		}

		if ($after = $field->get('after'))
		{
			return 'AFTER '.$this->quoteIdentifier($after).' ';
		}
	}

	public function compileUnsigned(Field $field)
	{
		if ( ! $field->get('unsigned'))
		{
			return;
		}

		if ($field->get('zerofill'))
		{
			return 'UNSIGNED ZEROFILL ';
		}

		return 'UNSIGNED ';
	}

	public function compileNullable(Field $field)
	{
		$null = $field->get('nullable') ?: $field->get('null');

		if ($null)
		{
			return 'NULL ';
		}

		return 'NOT NULL ';
	}

	public function compileFieldDefault(Field $field)
	{
		$type = strtoupper($field->type);

		if (isset($field->length))
		{
			$type .= '('.$field->get('length').')';
		}

		return $type;
	}

	public function compileFieldEnum(Field $field)
	{
		$options = (array) $field->get('options');
		$options = array_map(array($this, 'quoteIdentifier'), $options);

		return 'ENUM ('.implode(', ', $options). ') ';
	}

	public function compileFieldBoolean(Field $field)
	{
		if ( ! isset($field->default))
		{
			$field->default(0);
		}

		return 'TINYINT (0)';
	}

	public function compileFieldBool(Field $field)
	{
		return $this->compileFieldBoolean($field);
	}

	public function compileEngine(Table $table)
	{
		return '';
	}

	public function compileCharset(Collector $collector)
	{
		if ( ! $charset = $collector->get('charset'))
		{
			return;
		}

		if (($pos = strpos($charset, '_')) !== false)
		{
			$collector->collation = $charset;
			$charset = substr($charset, 0, $pos);
		}

		$default = $collector->get('charsetIsDefault') ? 'DEFAULT ' : '';
		$charset = $default.'CHARACTER SET '.$charset.' ';

		if ($collation = $collector->get('collation'))
		{
			$charset .= 'COLLATE '.$collation.' ';
		}

		return $charset;
	}

	public function getFieldDefaults()
	{
		return array(
			'name' => null,
			'type' => null,
			'length' => null,
			'null' => false,
			'default' => null,
			'primary' => false,
			'increment' => false,
		);
	}

	/**
	 * Get the base compiler
	 *
	 * @return  FuelPHP\Database\Compiler  compiler
	 */
	protected function getBaseCompiler()
	{
		if ( ! $this->baseCompiler)
		{
			$this->baseCompiler = $this->connection->getCompiler();
		}

		return $this->baseCompiler;
	}

	public function quote($value)
	{
		$compiler = $this->getBaseCompiler();

		return $compiler->quote($value);
	}

	public function quoteIdentifier($identifier)
	{
		$compiler = $this->getBaseCompiler();

		return $compiler->quoteIdentifier($identifier);
	}
}