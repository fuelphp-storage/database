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

class DB
{
	/**
	 * Query type contants.
	 */
	const PLAIN                 = 'Plain';
	const INSERT                = 'Insert';
	const SELECT                = 'Select';
	const UPDATE                = 'Update';
	const DELETE                = 'Delete';

	/**
	 * Create a new connection
	 *
	 * @param   array                        $config  connection config
	 * @return  Fuel\Database\Connection  connection object
	 * @throws  Fuel\Database\Exception   when there is no driver or the driver doesn't exist
	 */
	public static function connection(array $config)
	{
		if ( ! isset($config['driver']))
		{
			$config['driver'] = 'mysql';
		}

		if ( ! class_exists($class = 'Fuel\Database\Connection\\'.ucfirst($config['driver'])))
		{
			throw new Exception('Cannot create a connection without a valid driver');
		}

		return new $class($config);
	}

	/**
	 * Database expression shortcut.
	 *
	 * @param   mixed  $expression
	 * @return  object  a new Fuel\Database\Expression object.
	 */
	public static function expr($expression)
	{
		return new Expression($expression);
	}

	/**
	 * Database case shortcut.
	 *
	 * @param   string  $case
	 * @return  object  a new Fuel\Database\Expression\Case object.
	 */
	public static function when($case)
	{
		return new Expression\When($case);
	}

	/**
	 * Database match shortcut.
	 *
	 * @param   string  $fields
	 * @return  object  a new Fuel\Database\Expression\Match object.
	 */
	public static function match($fields)
	{
		return new Expression\Match($fields);
	}

	/**
	 * Database value shortcut.
	 *
	 * @param   mixed   $value  value
	 * @return  object  a new Fuel\Database\Expression\Value object.
	 */
	public static function value($value)
	{
		return new Expression\Value($value);
	}

	/**
	 * Database parameter shortcut.
	 *
	 * @param   mixed   $param  param
	 * @return  object  a new Fuel\Database\Expression\Parameter object.
	 */
	public static function param($param)
	{
		return new Expression\Parameter($param);
	}

	/**
	 * Database parameter shortcut.
	 *
	 * @param   mixed   $param  param
	 * @return  object  a new Fuel\Database\Expression\Increment object.
	 */
	public static function increment($field, $amount = 1)
	{
		$expression = new Expression\Increment($field);
		$expression->amount($amount);

		return $expression;
	}

	/**
	 * Database identifier shortcut.
	 *
	 * @param   mixed   $identifier  identifier
	 * @return  object  a new Fuel\Database\Expression\Identifier object.
	 */
	public static function identifier($identifier)
	{
		return new Expression\Identifier($identifier);
	}

	/**
	 * Database command shortcut.
	 *
	 * @param   string  $fn      command
	 * @param   array   $params  arguments
	 * @return  object  a new Fuel\Database\Expression\Command object.
	 */
	public static function command($command)
	{
		$arguments = func_get_args();
		$command = new Expression\Command(array_shift($arguments));
		$command->arguments = $arguments;

		return $command;
	}

	/**
	 * Returns a query object.
	 *
	 * @param   mixed   $query     raw database query
	 * @param   string  $type      query type
	 * @param   array   $bindings  query bindings
	 * @return  object  Fuel\Database\Query
	 */
	public static function query($query, $type = null)
	{
		return new Query($query, $type ?: static::PLAIN);
	}

	/**
	 * Created a select collector object.
	 *
	 * @param   mixed  string field name or arrays for alias
	 * ....
	 * @return  object  select query collector object
	 */
	public static function select($column = null)
	{
		$query =  new Collector\Select();
		return $query->selectArray(func_get_args());
	}

	/**
	 * Creates a select collector object.
	 *
	 * @param   array   $columns  array of fields to select
	 * @return  object  select query collector object
	 */
	public static function selectArray($columns = array())
	{
		return static::select()->selectArray($columns);
	}

	/**
	 * Creates an update collector object.
	 *
	 * @param   string   $table  table to update
	 * @param   array    $set    associative array of new values
	 * @return  object   update query collector object
	 */
	public static function update($table)
	{
		return new Collector\Update($table);
	}

	/**
	 * Creates a delete collector object.
	 *
	 * @param   string   $table  table to delete from
	 * @return  object   delete query collector object
	 */
	public static function delete($table = null)
	{
		return new Collector\Delete($table);
	}

	/**
	 * Creates an insert collector object.
	 *
	 * @param   string   $table  table to insert into
	 * @return  object   insert query collector object
	 */
	public static function insert($table)
	{
		return new Collector\Insert($table);
	}
}