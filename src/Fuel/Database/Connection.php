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

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Doctrine\DBAL\DriverManager as DoctrineManager;

abstract class Connection implements LoggerAwareInterface
{
	/**
	 * @param  string  $driver  pdo driver
	 */
	protected $driver;

	/**
	 * @var  array  $configDefaults  configuration defaults
	 */
	protected $configDefaults = array(
		'profiling'         => false,
		'asObject'          => true,
		'lateProperties'    => false,
		'resultCollection'  => null,
		'connection'        => null,
		'autoConnect'       => true,
		'dsn'               => false,
		'username'          => '',
		'password'          => '',
		'port'              => null,
		'host'              => 'localhost',
		'socket'            => null,
		'charset'           => 'utf8',
		'persistent'        => true,
		'attributes'        => array(),
		'insertIdField'     => 'id',
		'database'          => null,
		'logger'            => null,
		'pdo'               => null,
	);

	/**
	 * @var  array  $dsnParts  dsn partials
	 */
	protected $dsnParts = array(
		'host'   => 'host',
		'dbname' => 'database',
		'port'   => 'port',
		'unix_socket' => 'socket',
	);

	/**
	 * @var  array  $config  configuration
	 */
	public $config;

	/**
	 * @var  PDO  $pdo  PDO connection
	 */
	protected $pdo;

	/**
	 * @var  Fuel\Database\Compiler  $compiler  query compiler
	 */
	protected $compiler;

	/**
	 * @var  Fuel\Database\Compiler\Schema  $schemaCompiler  query schema compiler
	 */
	protected $schemaCompiler;

	/**
	 * @var  Fuel\Database\Schema  $schema  schema
	 */
	protected $schema;

	/**
	 * @var  Psr\Log\LoggerInterface  $logger  logger
	 */
	protected $logger;

	/**
	 * @var  integer  $savepoint  savepoint number
	 */
	protected $savepoint = 0;

	/**
	 * @var  array  $lastQuery  last query info
	 */
	protected $lastQuery;

	/**
	 * Constructor
	 *
	 * @param  array  $config  connection config
	 */
	public function __construct($config = array())
	{
		if (isset($config['pdo']))
		{
			$this->pdo = $config['pdo'];
		}

		if (isset($config['logger']))
		{
			$this->setLogger($config['logger']);
		}

		$this->setConfig($config);

		if ( ! $this->pdo and $this->config['autoConnect'])
		{
			$this->connect();
		}
	}

	/**
     * Sets a logger instance on the object
     *
     * @param  LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
    	$this->logger = $logger;
    }

	/**
	 * Retrieve the PDO connection object
	 *
	 * @return  PDO  PDO connection object
	 */
	public function getPdo()
	{
		if ( ! $this->pdo)
		{
			$this->connect();
		}

		return $this->pdo;
	}

	/**
	 * Retrieve the query compiler
	 *
	 * @return  Fuel\Database\Compiler  compiler object
	 */
	public function getCompiler()
	{
		if ( ! $this->compiler)
		{
			$driver = ucfirst($this->driver);
			$class = 'Fuel\Database\Compiler\\'.$driver;

			$this->compiler = new $class($this);
		}

		return $this->compiler;
	}

	/**
	 * Retrieve a schema instance
	 *
	 * @return  Schema  schema instance
	 */
	public function getSchema()
	{
		if ( ! $this->schema)
		{
			$this->schema = new Schema($this);
		}

		return $this->schema;
	}

	/**
	 * Get a docrine schema managers
	 *
	 * @return   \Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	public function getSchemaManager()
	{
		$connection = DoctrineManager::getConnection(array(
			'pdo' => $this->getPdo(),
			'dbname' => $this->config['database'],
		));

		return $connection->getSchemaManager();
	}

	/**
	 * Connect to the database
	 *
	 * @return  $this
	 */
	public function connect()
	{
		$dsn = $this->getDsn();

		try
		{
			$pdo = new PDO($dsn,
				$this->config['username'],
				$this->config['password'],
				$this->config['attributes']);
		}
		catch (PDOException $e)
		{
			$this->log('critical', 'Cloud not connect to database with DSN: {dsn}.', array(
				'dsn' => $dsn,
				'exception' => $e,
			));

			throw $e;
		}

		$this->pdo = $pdo;

		if ($this->config['charset'])
			$this->setCharset($this->config['charset']);

		return $this;
	}

	/**
	 * Set the connection charset
	 *
	 * @param   string  $charset  charset
	 * @return  $this
	 */
	public function setCharset($charset)
	{
		$this->getPdo()->exec('SET NAMES \''.$this->config['charset'].'\'');

		return $this;
	}

	/**
	 * Close the connection
	 *
	 * @return  $this
	 */
	public function close()
	{
		$this->pdo = null;

		return $this;
	}

	/**
	 * Set the connection config
	 *
	 * @param   array  $config  connection config
	 * @return  $this
	 */
	public function setConfig(array $config)
	{
		// Merge defaults
		$config = $config + $this->configDefaults;

		// exception mode
		$config['attributes'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

		if ($config['persistent'])
		{
			$config['attributes'][PDO::ATTR_PERSISTENT] = true;
		}

		$this->config = $config;

		return $this;
	}

	/**
	 * Retrieve the dsn from config
	 *
	 * @return  string  dsn string
	 */
	public function getDsn()
	{
		if ($this->config['dsn'])
		{
			return $this->config['dsn'];
		}

		$dsn = $this->driver.':';

		foreach ($this->dsnParts as $name => $key)
		{
			if (isset($this->config[$key]))
				$dsn .= $name.'='.$this->config[$key].';';
		}

		return $dsn;
	}

	/**
	 * Quote a value
	 *
	 * @param   mixed   $value  value
	 * @return  string  quoted value
	 */
	public function quote($value)
	{
		return $this->getCompiler()->quote($value);
	}

	/**
	 * Quote an identifier
	 *
	 * @param   string  $identifier  identifier
	 * @return  string  quoted identifier
	 */
	public function quoteIdentifier($idenfitier)
	{
		return $this->getCompiler()
			->quoteIdentifier($idenfitier);
	}

	/**
	 * Return last query
	 *
	 * @return  array  query info
	 */
	public function lastQueryInfo()
	{
		return $this->lastQuery;
	}

	/**
	 * Return last query sql
	 *
	 * @return  string  query sql
	 */
	public function lastQuery()
	{
		if ($info = $this->lastQueryInfo())
		{
			return $info['query'];
		}
	}

	/**
	 * Return last query params
	 *
	 * @return  string  query sql
	 */
	public function lastQueryParams()
	{
		if ($info = $this->lastQueryInfo())
		{
			return $info['params'];
		}
	}

	/**
	 * Return last query options
	 *
	 * @return  string  query sql
	 */
	public function lastQueryOptions()
	{
		if ($info = $this->lastQueryInfo())
		{
			return $info['options'];
		}
	}

	/**
     * Send log messages to the logger
     *
     * @param   mixed   $level
     * @param   string  $message
     * @param   array   $context
     * @return  null
     */
	public function log($level, $message, $context)
	{
		if ($this->logger)
		{
			$this->logger->log($level, $message, $context);
		}
	}

	/**
	 * Execute a query
	 *
	 * @param   string  $type     query type
	 * @param   mixed   $query    string query or query object
	 * @param   array   $params   query parameters
	 * @param   array   $options  query options
	 * @return  mixed   query result
	 */
	public function execute($type, $query, $params = array(), $options = array())
	{
		$type = ucfirst($type);
		$replacements = array();
		$input = array();

		$options = $options + array(
			'asObject' => $this->config['asObject'],
			'lateProperties' => $this->config['lateProperties'],
			'constructorArguments' => array(),
			'fetchInto' => null,
			'insertIdField' => $this->config['insertIdField'],
		);

		if ($query instanceof Query)
		{
			$query->setConnection($this);
			$param = array_merge($params, $query->getParams());

			foreach($query->getOptions() as $optionName => $optionValue)
			{
				if ($optionValue !== null)
				{
					$options[$optionName] = $optionValue;
				}
			}

			$query = $query->getQuery($this->getCompiler());
		}

		foreach ($params as $name => $value)
		{
			if ($value instanceof Expression) {
				$replacements[':'.$name] = $value->getValue($this);
			} else {
				$input[$name] = $value;
			}
		}

		if ( ! empty($replacements))
		{
			$names = array_keys($replacements);
			$values = array_values($replacements);
			$query = str_replace($names, $values, $query);
		}

		// Set the last query
		$this->lastQuery = array(
			'query' => $query,
			'params' => $input,
			'options' => $options
		);

		try
		{
			if (method_exists($this, 'execute'.$type))
			{
				return $this->{'execute'.$type}($query, $input, $options);
			}

			$statement = $this->getPdo()->prepare($query);

			return $statement->execute($input);
		}
		catch(PDOException $e)
		{
			$this->log('warning', 'Failed to execute query: {query}', array(
				'query'     => $query,
				'input'     => $input,
				'options'   => $options,
				'exception' => $e,
			));

			$code = is_int($e->getCode()) ? $e->getCode() : 0;
			throw new Exception($e->getMessage().' from query: '.$query.' with params: '.json_encode($input), $code);
		}
	}

	/**
	 * Execute an insert query
	 *
	 * @param   string  $query    string query
	 * @param   array   $params   query params
	 * @param   array   $options  query options
	 * @return  array   array with insert_id and number of inserted rows
	 */
	public function executeInsert($query, array $params, array $options)
	{
		$statement = $this->getPdo()
			->prepare($query);
		$statement->execute($params);

		return array(
			$this->getLastInsertId($statement, $options),
			$statement->rowCount(),
		);
	}

	/**
	 * Retrieve the last insert id from an insert query
	 *
	 * @param   PDOStatement  $statement
	 * @param   array         $options
	 * @return  mixed
	 */
	public function getLastInsertId($statement, array $options)
	{
		$id = $this->getPdo()
			->lastInsertId($options['insertIdField']);

		if (is_numeric($id))
		{
			return (int) $id;
		}

		return $id;
	}

	/**
	 * Execute a select query
	 *
	 * @param   string  $query    string query
	 * @param   array   $params   query params
	 * @param   array   $options  query options
	 * @return  array   result array
	 */
	protected function executeSelect($query, array $params, array $options)
	{
		$statement = $this->getPdo()
			->prepare($query);

		if ( ! $statement->execute($params))
		{
			return false;
		}

		$fetchStyle = PDO::FETCH_ASSOC;

		if ($options['fetchInto'])
		{
			$statement->setFetchMode(PDO::FETCH_INTO, $options['fetchInto']);
			$result = $statement->fetch();
			$statement->closeCursor();

			return $options['fetchInto'];
		}

		if ( ! $options['asObject'])
		{
			return $statement->fetchAll(PDO::FETCH_ASSOC);
		}
		elseif ($options['asObject'] === true)
		{
			return $statement->fetchAll(PDO::FETCH_OBJ);
		}

		$fetchStyle = PDO::FETCH_CLASS;

		if ($options['lateProperties'])
		{
			$fetchStyle = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;
		}

		return $statement->fetchAll($fetchStyle, $options['asObject'], $options['constructorArguments']);
	}

	/**
	 * Execute an update query
	 *
	 * @param   string  $query    string query
	 * @param   array   $params   query params
	 * @param   array   $options  query options
	 * @return  int     rows affected
	 */
	protected function executeUpdate($query, array $params, array $options)
	{
		$statement = $this->getPdo()->prepare($query);
		$statement->execute($params);

		return $statement->rowCount();
	}

	/**
	 * Execute a delete query
	 *
	 * @param   string  $query    string query
	 * @param   array   $params   query params
	 * @param   array   $options  query options
	 * @return  int     rows affected
	 */
	protected function executeDelete($query, $params, $options)
	{
		return $this->executeUpdate($query, $params, $options);
	}

	/**
	 * Start a transaction
	 *
	 * @return  $this
	 */
	public function beginTransaction()
	{
		return $this->setSavepoint();
	}

	/**
	 * Commit a transaction
	 *
	 * @return  $this
	 */
	public function commitTransaction()
	{
		return $this->releaseSavepoint();
	}

	/**
	 * Roll back a transaction
	 *
	 * @return  $this
	 */
	public function rollbackTransaction()
	{
		return $this->rollbackSavepoint();
	}

	/**
	 * Sets transaction savepoint.
	 *
	 * @param   string  $savepoint  savepoint name
	 * @return  $this
	 */
	public function setSavepoint($savepoint = null)
	{
		$savepoint or $savepoint = 'FUELPHP_SAVEPOINT_'. ++$this->savepoint;
		$this->pdo->exec('SAVEPOINT '.$savepoint);

		return $this;
	}

	/**
	 * Roll back to a transaction savepoint.
	 *
	 * @param   string  $savepoint  savepoint name
	 * @return  $this
	 */
	public function rollbackSavepoint($savepoint = null)
	{
		if ( ! $savepoint)
		{
			$savepoint = 'FUELPHP_SAVEPOINT_'.$this->savepoint;
			$this->savepoint--;
		}

		$this->pdo->exec('ROLLBACK TO SAVEPOINT '.$savepoint);

		return $this;
	}

	/**
	 * Release a transaction savepoint.
	 *
	 * @param   string  $savepoint  savepoint name
	 * @return  $this
	 */
	public function releaseSavepoint($savepoint = null)
	{
		if ( ! $savepoint)
		{
			$savepoint = 'FUELPHP_SAVEPOINT_'. $this->savepoint;
			$this->savepoint--;
		}

		$this->pdo->exec('RELEASE SAVEPOINT '.$savepoint);

		return $this;
	}

	/**
	 * DB class call forwarding. Sets the current connection if setter is available.
	 *
	 * @param   string  $func  function name
	 * @param   array   $args  function arguments
	 * @return  forwarded result (with set connection)
	 * @throws  \BadMethodCallException when method doesn't exist.
	 */
	public function __call($func, $args)
	{
		$call = 'Fuel\Database\DB::'.$func;

		if (is_callable($call))
		{
			$return = call_user_func_array($call, $args);

			if (is_object($return) and method_exists($return, 'setConnection'))
			{
				$return->setConnection($this);
			}

			return $return;
		}

		throw new \BadMethodCallException($func.' is not a method of '.get_called_class());
	}
}
