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

namespace Fuel\Database\Expression;

use Fuel\Database\Expression;
use Fuel\Database\Connection;

class Match extends Expression
{
	/**
	 * @var  string  $against  string to match against
	 */
	protected $against;

	/**
	 * @var  boolean  $boolean  wether to use boolean mode
	 */
	protected $boolean = false;

	/**
	 * @var  boolean  $expand  wether to use query expansion
	 */
	protected $expand = false;

	/**
	 * Set the against value
	 *
	 * @param   string  $string  against string
	 * @return  $this
	 */
	public function against($string)
	{
		$this->against = $string;

		return $this;
	}

	/**
	 * Set wether to use IN BOOLEAN MODE
	 *
	 * @param   boolean  $mode  wether to use IN BOOLEAN MODE
	 * @return  $this
	 */
	public function boolean($mode = true)
	{
		if ($this->expand and $mode)
		{
			$this->expand = false;
		}

		$this->boolean = $mode;

		return $this;
	}

	/**
	 * Set wether to use WITH QUERY EXPANSION
	 *
	 * @param   boolean  $expand  wether to use WITH QUERY EXPANSION
	 * @return  $this
	 */
	public function expand($expand = true)
	{
		if ($this->boolean and $expand)
		{
			$this->boolean = false;
		}

		$this->expand = $expand;

		return $this;
	}

	/**
	 * Return the quoted identifier.
	 *
	 * @return  mixed  expression
	 */
	public function getValue(Connection $connection)
	{
		$compiler = $connection->getCompiler();
		$identifiers = $compiler->compileIdentifiers((array) $this->value);

		$sql = 'MATCH ('.$identifiers.') AGAINST (';
		$sql .= $connection->quote($this->against);

		if ($this->boolean)
		{
			$sql .= ' IN BOOLEAN MODE';
		}
		elseif ($this->expand)
		{
			$sql .= ' WITH QUERY EXPANSION';
		}

		return $sql.')';
	}
}