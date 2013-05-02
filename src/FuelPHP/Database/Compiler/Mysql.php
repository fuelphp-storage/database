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

namespace FuelPHP\Database\Compiler;

use FuelPHP\Database\Compiler;

class Mysql extends Compiler
{
	/**
	 * @var  string  $tableQuote  table quote
	 */
	public $tableQuote = '`';

	public function compileCommandConcat($params)
	{
		$params = array_map(array($this, 'quoteIdentifier'), $params);

		return 'CONCAT('.implode(', ', $params).')';
	}
}