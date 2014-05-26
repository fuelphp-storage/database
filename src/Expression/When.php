<?php

namespace Fuel\Database\Expression;

use Fuel\Database\Connection;
use Fuel\Database\Expression;

class When extends Expression
{
	public $when = array();

	public $orElse;

	public function is($value, $then)
	{
		$this->when[] = compact('value', 'then');

		return $this;
	}

	public function orElse($value)
	{
		$this->orElse = $value;

		return $this;
	}

		/**
	 * Return the expression.
	 *
	 * @return  mixed  expression
	 */
	public function getValue(Connection $connection)
	{
		$compiler = $connection->getCompiler();

		return $compiler->compileCase($this);
	}
}