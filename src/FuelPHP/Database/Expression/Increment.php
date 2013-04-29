<?php

namespace FuelPHP\Database\Expression;

use FuelPHP\Database\Expression;
use FuelPHP\Database\Connection;

class Increment extends Expression
{
	/**
	 * @var  integer  $amount  amount to increment by
	 */
	protected $amount = 1;

	/**
	 * Sets the amount to increment by
	 *
	 * @param   integer  $amount  increment amount
	 * @return  $this
	 */
	public function amount($amount)
	{
		$this->amount = $amount;

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

		return $compiler->compileIncrement($this->value, $this->amount);
	}
}