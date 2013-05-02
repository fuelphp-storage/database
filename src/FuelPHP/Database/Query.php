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

class Query implements ConnectionAwareInterface
{
	/**
	 * @var  $type  const  query type
	 */
	public $type = DB::PLAIN;

	/**
	 * @param  $query  string  query
	 */
	public $query;

	/**
	 * @param  array  $params  query parameters
	 */
	public $params = array();

	/**
	 * @param  Connection  $connection  connection object
	 */
	public $connection;

	/**
	 * @param  Compiler  $compiler  compiler object
	 */
	public $compiler;

	/**
	 * @param  array  $constructorArguments  constructor arguments
	 */
	public $constructorArguments = array();

	/**
	 * @param  string  $asObject  select retrieve style
	 */
	public $asObject;

	/**
	 * @param  boolean|null  $lateProperties  wether to use late fetching of properties
	 */
	public $lateProperties;

	/**
	 *  @var  string|null  $resultCollection  result collection classname
	 */
	public $resultCollection;

	/**
	 * @param  object  $fetchInto  object to fetch into
	 */
	public $fetchInto;

	/**
	 * @var  string  $insertIdField  field used for lastInsertId
	 */
	public $insertIdField;

	/**
	 * Constructor
	 *
	 * @param  string  $query  query
	 * @param  const   $type   query type
	 */
	public function __construct($query, $type = DB::PLAIN)
	{
		$this->query = $query;
		$this->type = $type;
	}

	/**
	 * Sets/Gets the field used for lastInsertId
	 *
	 * @param   string  $field  insert id field
	 * @return  $this
	 */
	public function insertIdField($field)
	{
		$this->insertIdField = $field;

		return $this;
	}

	/**
	 * Retrieve the last insert ID field
	 *
	 * @param   Connection  $connection
	 * @return  string
	 */
	public function getInsertIdField(Connection $connection = null)
	{
		if (($field = $this->insertIdField) !== null)
		{
			return $field;
		}

		if ($connection or $connection = $this->connection)
		{
			return $connection->config['insertIdField'];
		}
	}

	/**
	 * Set the constructor arguments
	 *
	 * @param   array  $arguments  constructor arguments
	 * @return  $this
	 */
	public function withArguments(array $arguments)
	{
		$this->constructorArguments = $arguments;

		return $this;
	}

	/**
	 * Set wether to use the late properties fetch style
	 *
	 * @param   bool  $late  true|false
	 * @return  $this
	 */
	public function lateProperties($late = true)
	{
		$this->lateProperties = $late;

		return $this;
	}

	/**
	 * Set the object to fetch into
	 *
	 * @param   object  $object  object to fetch into
	 * @return  $this
	 */
	public function fetchInto(&$object)
	{
		$this->fetchInto = $object;

		return $this;
	}

	/**
	 * Get the query options
	 *
	 * @param   Connection  $connection  connection instance
	 * @return  array       query options
	 */
	public function getOptions(Connection $connection = null)
	{
		$asObject = $this->asObject;
		$lateProperties = $this->lateProperties;
		$resultCollection = $this->resultCollection;

		if ( ! $connection)
		{
			$connection = $this->connection;
		}

		if ($asObject === null and $connection)
		{
			$asObject = $connection->config['asObject'];
		}

		if ($lateProperties === null and $connection)
		{
			$lateProperties = $connection->config['lateProperties'];
		}

		if ($resultCollection === null and $connection)
		{
			$resultCollection = $connection->config['resultCollection'];
		}

		return array(
			'asObject' => $asObject,
			'lateProperties' => $lateProperties,
			'constructorArguments' => $this->constructorArguments,
			'fetchInto' => $this->fetchInto,
			'resultCollection' => $resultCollection,
			'insertIdField' => $this->getInsertIdField($connection),
		);
	}

	/**
	 * Set object fetching
	 *
	 * @param   mixed  $class  bool or class name
	 * @return  $this
	 */
	public function asObject($class = true)
	{
		$this->asObject = $class;

		return $this;
	}

	/**
	 * Set fetch style to array
	 *
	 * @return  $this
	 */
	public function asAssoc()
	{
		return $this->asObject(false);
	}

	/**
	 * Get the query parameters
	 *
	 * @return  array  query parameters
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Set a single query parameter
	 *
	 * @param   string  $name  param name
	 * @param   mixed   $name  param value
	 * @return  $this
	 */
	public function setParam($name, $value)
	{
		$this->params[$name] = $value;

		return $this;
	}

	/**
	 * Set an array of query parameters
	 *
	 * @param   array  $params  query parameters
	 * @return  $this
	 */
	public function setParams(array $params)
	{
		$this->params = array_merge($this->params, $params);

		return $this;
	}

	/**
	 * Bind a query parameter
	 *
	 * @param   string  $name   param name
	 * @param   mixed   $value  bound value
	 * @return  $this
	 */
	public function bindParam($name, &$value)
	{
		$this->params[$name] = &$value;

		return $this;
	}

	/**
	 * Set the query connection
	 *
	 * @param   Connection  $connection  connection object
	 * @return  $this
	 */
	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;
		$this->compiler = $connection->getCompiler();

		return $this;
	}

	/**
	 * Compile the query
	 *
	 * @param   Compiler  $compiler  query compiler
	 * @return  string    sql query
	 */
	public function getQuery(Compiler $compiler = null)
	{
		return $this->query;
	}

	/**
	 * Execute a query
	 *
	 * @param   array       $params      additional query parameters
	 * @param   Connection  $connection  connection object
	 */
	public function execute(array $params = null, Connection $connection = null)
	{
		$connection or $connection = $this->connection;
		$params and $this->setParams($params);

		if ( ! $connection)
		{
			throw new Exception('Cannot execute a query without a connection.');
		}

		return $connection->execute($this->type, $this, $this->params);
	}
}