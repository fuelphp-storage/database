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

namespace FuelPHP\Database\Collector;

use FuelPHP\Database\Collector;

class Where extends Collector
{
	/**
	 * @var  array  $where  where conditions
	 */
	public $where = array();

	/**
	 * @var  array  $orderBy  ORDER BY clause
	 */
	public $orderBy = array();

	/**
	 * @var  integer  $limit  query limit
	 */
	public $limit;

	/**
	 * @var  array  $offset  query offset
	 */
	public $offset;

	/**
	 * Alias for andWhere.
 	 *
	 * @param   mixed   $column  array of 'and where' statements or column name
	 * @param   string  $op      where logic operator
	 * @param   mixed   $value   where value
	 * @return  $this
	 */
	public function where($column, $op = null, $value = null)
	{
		return call_user_func_array(array($this, 'andWhere'), func_get_args());
	}

	/**
	 * Adds multiple 'where' statements.
	 *
	 * @param   array   $where      where statements
	 * @param   string  $chaining   statement chaining
	 * @return  $this
	 */
	public function whereArray(array $where, $chaining = 'and')
	{
		$callback = array($this, $chaining.'Where');

		foreach($where as $column => $clause)
		{
			$clause = (array) $clause;

			if ( ! is_int($column))
			{
				array_unshift($clause, $column);
			}

			call_user_func_array($callback, $clause);
		}

		return $this;
	}

	/**
	 * Adds an 'and where' statement to the query.
	 *
	 * @param   mixed   $column  array of 'and where' statements or column name
	 * @param   string  $op      where logic operator
	 * @param   mixed   $value   where value
	 * @return  $this
	 */
	public function andWhere($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->andWhereOpen();
			$column($this);
			$this->whereClose();

			return $this;
		}

		if (func_num_args() === 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('where', 'and', $column, $op, $value);
	}

	/**
	 * Adds multiple 'and where' statements.
	 *
	 * @param   array  $where  where statements
	 * @return  $this
	 */
	public function andWhereArray(array $where)
	{
		return $this->whereArray($where, 'and');
	}

	/**
	 * Adds an 'or where' statement to the query.
	 *
	 * @param   mixed   $column  array of 'or where' statements or column name
	 * @param   string  $op      where logic operator
	 * @param   mixed   $value   where value
	 * @return  $this
	 */
	public function orWhere($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->orWhereOpen();
			$column($this);
			$this->whereClose();

			return $this;
		}

		if (func_num_args() === 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('where', 'or', $column, $op, $value);
	}

	/**
	 * Adds multiple 'or where' statements.
	 *
	 * @param   array  $where  where statements
	 * @return  $this
	 */
	public function orWhereArray(array $where)
	{
		return $this->whereArray($where, 'or');
	}

	/**
	 * Alias for andWhere.
 	 *
	 * @param   mixed   $column  array of 'and not where' statements or column name
	 * @param   string  $op      where logic operator
	 * @param   mixed   $value   where value
	 * @return  $this
	 */
	public function notWhere($column, $op = null, $value = null)
	{
		return call_user_func_array(array($this, 'andNotWhere'), func_get_args());
	}

	/**
	 * Adds multiple 'not where' statements.
	 *
	 * @param   array  $where  where statements
	 * @return  $this
	 */
	public function notWhereArray(array $where)
	{
		return $this->whereArray($where, 'not');
	}

	/**
	 * Adds an 'and not where' statement to the query.
	 *
	 * @param   mixed   $column  array of 'and where' statements or column name
	 * @param   string  $op      where logic operator
	 * @param   mixed   $value   where value
	 * @return  $this
	 */
	public function andNotWhere($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->andNotWhereOpen();
			$column($this);
			$this->whereClose();

			return $this;
		}

		if (func_num_args() === 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('where', 'and', $column, $op, $value, true);
	}

	/**
	 * Adds multiple 'not where' statements.
	 *
	 * @param   array  $where  where statements
	 * @return  $this
	 */
	public function andNotWhereArray(array $where)
	{
		return $this->whereArray($where, 'not');
	}

	/**
	 * Adds an 'or not where' statement to the query.
	 *
	 * @param   mixed   $column  array of 'or where' statements or column name
	 * @param   string  $op      where logic operator
	 * @param   mixed   $value   where value
	 * @return  $this
	 */
	public function orNotWhere($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->orNotWhereOpen();
			$column($this);
			$this->whereClose();

			return $this;
		}

		if (func_num_args() === 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('where', 'or', $column, $op, $value, true);
	}

	/**
	 * Adds multiple 'not where' statements.
	 *
	 * @param   array  $where  where statements
	 * @return  $this
	 */
	public function orNotWhereArray(array $where)
	{
		return $this->whereArray($where, 'orNot');
	}

	/**
	 * Opens an 'and where' nesting.
	 *
	 * @return  $this
	 */
	public function whereOpen()
	{
		$this->where[] = array(
			'type' => 'and',
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'and where' nesting.
	 *
	 * @return  $this
	 */
	public function whereClose()
	{
		$this->where[] = array(
			'nesting' => 'close',
		);

		return $this;
	}

	/**
	 * Opens an 'and where' nesting.
	 *
	 * @return  $this
	 */
	public function andWhereOpen()
	{
		$this->where[] = array(
			'type' => 'and',
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'and where' nesting.
	 *
	 * @return  $this
	 */
	public function andWhereClose()
	{
		return $this->whereClose();
	}

	/**
	 * Opens an 'or where' nesting.
	 *
	 * @return  $this
	 */
	public function orWhereOpen()
	{
		$this->where[] = array(
			'type' => 'or',
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'or where' nesting.
	 *
	 * @return  $this
	 */
	public function orWhereClose()
	{
		return $this->whereClose();
	}

	/**
	 * Opens an 'and not where' nesting.
	 *
	 * @return  $this
	 */
	public function notWhereOpen()
	{
		$this->where[] = array(
			'type' => 'and',
			'not' => true,
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'and not where' nesting.
	 *
	 * @return  $this
	 */
	public function notWhereClose()
	{
		return $this->whereClose();
	}

	/**
	 * Opens an 'and not where' nesting.
	 *
	 * @return  $this
	 */
	public function andNotWhereOpen()
	{
		$this->where[] = array(
			'type' => 'and',
			'not' => true,
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'and not where' nesting.
	 *
	 * @return  $this
	 */
	public function andNotWhereClose()
	{
		return $this->whereClose();
	}

	/**
	 * Opens an 'or not where' nesting.
	 *
	 * @return  $this
	 */
	public function orNotWhereOpen()
	{
		$this->where[] = array(
			'type' => 'or',
			'not' => true,
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'or where' nesting.
	 *
	 * @return  $this
	 */
	public function orNotWhereClose()
	{
		return $this->whereClose();
	}

	/**
	 * Adds a condition to a condition stack
	 *
	 * @param   string   $stack   condition stack
	 * @param   string   $type    chain type
	 * @param   mixed    $column  array of 'where' statements or column name
	 * @param   string   $op      where logic operator
	 * @param   mixed    $value   where value
	 * @param   boolean  $not     wether to use NOT
	 * @return  object   current instance
	 */
	protected function addCondition($stack, $type, $column, $op, $value, $not = false)
	{
		$this->{$stack}[] = array(
			'type' => $type,
			'field' => $column,
			'op' => $op,
			'value' => $value,
			'not' => $not,
		);

		return $this;
	}

	/**
	 * Adds an 'order by' statment to the query.
	 *
	 * @param   string|array  $column     array of staments or column name
	 * @param   string        $direction  optional order direction
	 * @return  object        current instance
	 */
	public function orderBy($column, $direction = null)
	{
		if ( ! is_array($column))
		{
			$this->orderBy[] = array(
				'column' => $column,
				'direction' => $direction,
			);

			return $this;
		}

		foreach ($column as $key => $val)
		{
			if (is_numeric($key))
			{
				$key = $val;
				$val = null;
			}

			$this->orderBy[] = array(
				'column' => $key,
				'direction' => $val,
			);
		}

		return $this;
	}

	/**
	 * Sets a limit [and offset] for the query
	 *
	 * @param   int     limit integer
	 * @param   int     offset integer
	 * @return  $this
	 */
	public function limit($limit, $offset = null)
	{
		$this->limit = (int) $limit;
		func_num_args() > 1 and $this->offset = (int) $offset;

		return $this;
	}

	/**
	 * Sets an offset for the query
	 *
	 * @param   int     offset integer
	 * @return  $this
	 */
	public function offset($offset)
	{
		$this->offset = (int) $offset;

		return $this;
	}
}