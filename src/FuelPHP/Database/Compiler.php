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

use FuelPHP\Database\Expression;
use FuelPHP\Database\Expression\When;

abstract class Compiler
{
	/**
	 * @var  FuelPHP\Database\Connection  $connection  connection
	 */
	protected $connection;

	/**
	 * @var  string  $tableQuote  table quote
	 */
	public $tableQuote = '"';

	/**
	 * @var  array  $queryPartials  compile suffixes for query compiling
	 */
	protected $queryPartials = array(
		'select' => array(
			'Select', 'From', 'Join', 'Where',
			'GroupBy', 'Having', 'OrderBy',
			'LimitOffset',
		),
		'insert' => array(
			'Insert', 'Values', 'Returning',
		),
		'update' => array(
			'Update', 'Set', 'Where', 'OrderBy',
			'LimitOffset',
		),
		'delete' => array(
			'Delete', 'Where', 'OrderBy',
			'LimitOffset',
		),
	);

	/**
	 * Constructor
	 *
	 * @param  FuelPHP\Database\Connection  $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Compiles the query
	 *
	 * @param  FuelPHP\Database\Collector  $collector  query collector
	 */
	public function compile($collector)
	{
		$type = strtolower($collector->type);
		$partials = $this->queryPartials[$type];
		$sql = array();

		foreach ($partials as $partial)
		{
			if($part = $this->{'compile'.$partial}($collector))
			{
				$sql[] = $part;
			}
		}

		return implode(' ', $sql);
	}

	/**
	 * Compiles an increment statement.
	 *
	 * @param   string   $field   base value field
	 * @param   integer  $amount  numeric mutation
	 * @return  string   sql statement
	 */
	public function compileIncrement($field, $amount)
	{
		$modifier = $amount > 0 ? ' + ' : ' - ';

		return $this->quoteIdentifier($field).$modifier.$amount;
	}

	/**
	 * Compile a CASE statement
	 *
	 * @param   Expression\When  $case
	 * @return  string  case sql
	 */
	public function compileCase(When $case)
	{
		$sql = 'CASE '.$this->quoteIdentifier($case->value);

		foreach($case->when as $when)
		{
			$sql .= ' WHEN '.$this->quote($when['value']).' THEN '.$this->quote($when['then']);
		}

		return $sql.' ELSE '.$this->quote($case->orElse).' END';
	}

	/**
	 * Compiles a CONCAT statement
	 *
	 * @param   array   $params  parameters to concat
	 * @return  string  concat statement
	 */
	public function compileCommandConcat($params)
	{
		$params = array_map(array($this, 'quoteIdentifier'), $params);

		return implode(' || ', $params);
	}

	/**
	 * Compiles query identifiers
	 *
	 * @param   array   $identifiers  identifiers
	 * @return  string  quoted identifiers
	 */
	public function compileIdentifiers(array $identifiers)
	{
		$callback = array($this, 'quoteIdentifier');
		$identifiers = array_map($callback, $identifiers);

		return implode(', ', $identifiers);
	}

	/**
	 * Compile an insert query
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     insert sql
	 */
	public function compileInsert(Collector $collector)
	{
		return 'INSERT INTO '.$this->quoteIdentifier($collector->table);
	}

	/**
	 * Compile a returning statement
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     returning sql
	 */
	public function compileReturning(Collector $collector)
	{
		return '';
	}

	/**
	 * Compile a select query
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     select sql
	 */
	public function compileSelect(Collector $collector)
	{
		$columns = '*';

		if ( ! empty($collector->columns))
		{
			$columns = $this->compileIdentifiers((array) $collector->columns);
		}

		$sql = 'SELECT ';

		if ($collector->distinct)
		{
			$sql .= 'DISTINCT ';
		}

		return $sql.$columns;
	}

	/**
	 * Compile an update query
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     update sql
	 */
	public function compileUpdate(Collector $collector)
	{
		return 'UPDATE '.$this->quoteIdentifier($collector->table);
	}

	/**
	 * Compile a delete query
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     delete sql
	 */
	public function compileDelete(Collector $collector)
	{
		return 'DELETE FROM '.$this->quoteIdentifier($collector->table);
	}

	/**
	 * Compile FROM
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     FROM sql
	 */
	public function compileFrom(Collector $collector)
	{
		$tables = $this->compileIdentifiers((array) $collector->table);

		return 'FROM '.$tables;
	}

	/**
	 * Compile a subquery
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     subquery sql
	 */
	public function compileSubQuery(Query $query)
	{
		return '('.$query->getQuery($this->connection).')';
	}

	/**
	 * Compile WHERE
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     WHERE sql
	 */
	public function compileWhere(Collector $collector)
	{
		if ( ! empty($collector->where))
		{
			return 'WHERE '.$this->compileConditions($collector->where);
		}
	}

	/**
	 * Compile HAVING
	 *
	 * @param   Collector  $collection  query collector
	 * @return  string     HAVING sql
	 */
	public function compileHaving(Collector $collector)
	{
		if ( ! empty($collector->having))
		{
			return 'HAVING '.$this->compileConditions($collector->having);
		}
	}

	/**
	 * Compiles SET.
	 *
	 * @return  string  compiled set part
	 */
	protected function compileSet(Collector $collector)
	{
		if ( ! empty($collector->values))
		{
			$parts = array();

			foreach ($collector->values as $k => $v)
			{
				$parts[] = $this->quoteIdentifier($k).' = '.$this->quote($v);
			}

			return 'SET '.join(', ', $parts);
		}
	}

	/**
	 * Compiles the insert values.
	 *
	 * @param   FuelPHP\Database\Collector  $collector  insert collector
	 * @return  string                      compiled values part
	 */
	protected function compileValues(Collector $collector)
	{
		$rows = array();

		foreach ($collector->values as $row)
		{
			$parts = array();

			foreach ($collector->columns as $c)
			{
				if (array_key_exists($c, $row))
				{
					$parts[] = $this->quote($row[$c]);
				}
				else
				{
					$parts[] = 'NULL';
				}
			}

			$rows[] = '('.implode(', ', $parts).')';
		}

		$columns = array_map(array($this, 'quoteIdentifier'), $collector->columns);
		$sql = ' ('.join(', ', $columns).') VALUES ';

		return $sql.join(', ', $rows);
	}

	/**
	 * Compiles the group by part.
	 *
	 * @return  string  compiler group by part
	 */
	protected function compileGroupBy(Collector $collector)
	{
		if ( ! empty($collector->groupBy))
		{
			$callback = array($this, 'quoteIdentifier');

			return 'GROUP BY '.implode(', ', array_map($callback, $collector->groupBy));
		}
	}

	/**
	 * Compiles the order by part.
	 *
	 * @return  string  compiled order by part
	 */
	protected function compileOrderBy(Collector $collector)
	{
		if ( ! empty($collector->orderBy))
		{
			$sort = array();

			foreach ($collector->orderBy as $orderBy)
			{
				extract($orderBy);

				if ( ! empty($direction))
				{
					// Make the direction uppercase
					$direction = ' '.strtoupper($direction);
				}

				$sort[] = $this->quoteIdentifier($column).$direction;
			}

			return 'ORDER BY '.implode(', ', $sort);
		}
	}

	/**
	 * Compiles the limit and offset statement.
	 *
	 * @return  string  compiled limit and offset statement
	 */
	public function compileLimitOffset(Collector $collector)
	{
		$sql = '';

		if ($collector->limit)
		{
			$sql .= ' LIMIT '.$collector->limit;
		}

		if ($collector->offset)
		{
			$sql .= ' OFFSET '.$collector->offset;
		}

		if ( ! empty($sql))
		{
			return trim($sql);
		}
	}

	/**
	 * Compiles the join part.
	 *
	 * @return  string  compiled join part
	 */
	public function compileJoin(Collector $collector)
	{
		$return = array();

		if (empty($collector->join))
		{
			return null;
		}

		foreach ($collector->join as $join)
		{
			if ($join->type)
			{
				$sql = strtoupper($join->type).' JOIN';
			}
			else
			{
				$sql = 'JOIN';
			}

			// Quote the table name that is being joined
			$sql .= ' '.$this->quoteIdentifier($join->table).' ON ';

			$on_sql = '';
			foreach ($join->on as $condition)
			{
				// Split the condition
				list($c1, $op, $c2, $andOr) = $condition;

				if ($op)
				{
					// Make the operator uppercase and spaced
					$op = ' '.strtoupper($op);
				}

				// Quote each of the identifiers used for the condition
				$on_sql .= (empty($on_sql) ? '' : ' '.$andOr.' ').$this->quoteIdentifier($c1).$op.' '.$this->quoteIdentifier($c2);
			}

			empty($on_sql) or $sql .= '('.$on_sql.')';

			$return[] = $sql;
		}

		return implode(' ', $return);
	}

	/**
	 * Compiles conditions for where and having statements.
	 *
	 * @param   array   $conditions  conditions array
	 * @return  string  compiled conditions
	 */
	protected function compileConditions($conditions)
	{
		$last = false;
		$parts = array();

		foreach ($conditions as $c)
		{
			if ( ! empty($parts) and $last !== '(')
			{
				$parts[] = ' '.strtoupper($c['type']).' ';
			}

			if ($useNot = (isset($c['not']) and $c['not']))
			{
				$parts[] = count($parts) > 0 ? 'NOT ' : ' NOT ';
			}

			if (isset($c['nesting']))
			{
				if ($c['nesting'] === 'open')
				{
					$last = '(';
					$parts[] = '(';
				}
				else
				{
					array_pop($parts);

					if ($useNot)
					{
						array_pop($parts);
						$parts[] = ' NOT ';
					}

					$last = ')';
					$parts[] = ')';
				}

				continue;
			}

			$last = false;
			$c['op'] = trim($c['op']);

			if ($c['value'] === null)
			{
				if ($c['op'] === '!=')
				{
					$c['op'] = 'IS NOT';
				}
				elseif ($c['op'] === '=')
				{
					$c['op'] = 'IS';
				}
			}
			else
			{
				$c['op'] = strtoupper($c['op']);
			}

			if($c['op'] === 'BETWEEN' and is_array($c['value']))
			{
				list($min, $max) = $c['value'];
				$c['value'] = $this->quote($min).' AND '.$this->quote($max);
			}
			else
			{
				$c['value'] = $this->quote($c['value']);
			}

			$c['field'] = $this->quoteIdentifier($c['field']);
			$parts[] = $c['field'].' '.$c['op'].' '.$c['value'];
		}

		return implode($parts);
	}

	/**
	 * Quote a value for an SQL query.
	 *
	 * Objects passed to this function will be converted to strings.
	 * Expression objects will use the value of the expression.
	 * Query objects will be compiled and converted to a sub-query.
	 * Command objects will be send of for compiling.
	 * All other objects will be converted using the `__toString` method.
	 *
	 * @param   mixed   any value to quote
	 * @return  string
	 */
	public function quote($value)
	{

		if ($value === '?')
		{
			return $value;
		}

		elseif ($value === null)
		{
			return 'NULL';
		}

		elseif (is_bool($value))
		{
			return "'".(string) $value."'";
		}

		elseif ($value instanceof Query)
		{
			// create a sub-query
			return '('.$value->getQuery($this).')';
		}

		elseif ($value instanceof Expression)
		{
			// get the output from the expression
			return $value->getValue($this->connection);
		}

		elseif (is_array($value))
		{
			$value = array_map(array($this, 'quote'), $value);

			return '('.implode(', ', $value).')';
		}

		elseif (is_int($value))
		{
			return $value;
		}

		elseif (is_double($value))
		{
			return $value;
		}

		elseif (is_float($value))
		{
			// Convert to non-locale aware d to prevent possible commas
			return sprintf('%F', $value);
		}

		return $this->connection->getPdo()->quote($value);
	}

	/**
	 * Quotes an identifier
	 *
	 * @param   mixed   $value  value to quote
	 * @return  string  quoted identifier
	 */
	public function quoteIdentifier($value)
	{
		if ($value === '*')
		{
			return '*';
		}

		// Compile a subquery
		if ($value instanceof Query)
		{
			return '('.$value->getQuery($this).')';
		}

		// Compile an expression
		elseif ($value instanceof Expression)
		{
			// Use a raw expression
			return $value->getValue($this->connection);
		}

		// Compile an alias
		else if (is_array($value))
		{
			// Separate the column and alias
			list ($_value, $alias) = $value;

			return $this->quoteIdentifier($_value).' AS '.$this->quoteIdentifier($alias);
		}

		// Convert all other types to string
		$value = (string) $value;

		if (strpos($value, '"') !== false)
		{
			// Quote the column in FUNC("ident") identifiers
			$quoter = array($this, 'quoteIdentifier');
			$callback = function ($matches) use ($quoter) {
				return call_user_func($quoter, trim($matches[0], '"'));
			};

			return preg_replace_callback('/"(.+?)"/', $callback, $value);
		}
		elseif (strpos($value, '.') !== false)
		{
			// Split the identifier into the individual parts
			$parts = explode('.', $value);

			// Quote each of the parts
			return implode('.', array_map(array($this, 'wrapIdentifier'), $parts));
		}

		return $this->wrapIdentifier($value);
	}

	/**
	 * Wraps an identifier
	 *
	 * @param   string  $identifier  identifier
	 * @return  string  wrapped identifier
	 */
	public function wrapIdentifier($identifier)
	{
		return $this->tableQuote.$identifier.$this->tableQuote;
	}
}