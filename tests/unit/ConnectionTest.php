<?php

namespace Fuel\Database;

use Codeception\TestCase\Test;
use Mockery as M;
use PDO;
use stdClass;

class ConnectionTest extends Test
{
	public function testConstruct()
	{
		$connection = DB::connection(array(
			'autoConnect' => false,
			'driver' => 'mysql',
			'persistent' => true,
			'username' => 'root',
		));

		$this->assertInstanceOf('PDO', $connection->getPdo());

		$connection = DB::connection(array(
			'driver' => 'mysql',
			'username' => 'root',
		));

		$this->assertInstanceOf('PDO', $connection->getPdo());
	}

	public function testPdoInjection()
	{
		$connection = DB::connection(array(
			'pdo' => 'injection'
		));

		$this->assertSame('injection', $connection->getPdo());
		$connection->close();
		$this->assertAttributeEquals(null, 'pdo', $connection);
	}

	public function testLogger()
	{
		$logger = M::mock('Psr\Log\LoggerInterface');
		$connection = DB::connection(array());
		$connection->setLogger($logger);
		$this->assertAttributeEquals($logger, 'logger', $connection);

		$logger = M::mock('Psr\Log\LoggerInterface');
		$connection = DB::connection(array(
			'logger' => $logger,
		));
		$this->assertAttributeEquals($logger, 'logger', $connection);
		$logger->shouldReceive('log')->with('warning', 'message', array('context'));
		$connection->log('warning', 'message', array('context'));
	}

	public function testGetSupportClasses()
	{
		$connection = DB::connection(array(
			'dsn' => 'mysql:',
			'username' => 'root',
		));
		$this->assertInstanceOf('Fuel\Database\Compiler', $connection->getCompiler());
		$this->assertInstanceOf('Doctrine\DBAL\Schema\AbstractSchemaManager', $connection->getSchemaManager());
		$this->assertInstanceOf('Fuel\Database\Query', $connection->query('Statement'));
		$this->assertInstanceOf('Fuel\Database\Schema', $connection->getSchema());
		$this->assertInstanceOf('Fuel\Database\Collector\Select', $connection->select('Statement'));
	}

	/**
	 * @expectedException  \BadMethodCallException
	 */
	public function testInvalidMethodCall()
	{
		$connection = DB::connection(array());
		$connection->badMethod();
	}

	/**
	 * @expectedException  \PDOException
	 */
	public function testInvalidDsn()
	{
		DB::connection(array(
			'dsn' => 'mysq:siodhv',
		))->connect();
	}

	public function testPdoCalls()
	{
		$pdo = M::mock('stdClass');
		$statement = M::mock('stdClass');
		$connection = DB::connection(array(
			'pdo' => $pdo,
		));

		$pdo->shouldReceive('exec')->with('SAVEPOINT FUELPHP_SAVEPOINT_1');
		$pdo->shouldReceive('exec')->with('SAVEPOINT FUELPHP_SAVEPOINT_2');
		$pdo->shouldReceive('exec')->with('ROLLBACK TO SAVEPOINT FUELPHP_SAVEPOINT_2');
		$pdo->shouldReceive('exec')->with('RELEASE SAVEPOINT FUELPHP_SAVEPOINT_1');
		$pdo->shouldReceive('quote')->with('this')->andReturn('that');
		$connection->beginTransaction();
		$connection->beginTransaction();
		$connection->rollbackTransaction();
		$connection->commitTransaction();
		$this->assertNull($connection->lastQuery());
		$this->assertNull($connection->lastQueryParams());
		$this->assertNull($connection->lastQueryOptions());
		$this->assertEquals('that', $connection->quote('this'));
		$this->assertEquals('`name`', $connection->quoteIdentifier('name'));
		$pdo->shouldReceive('prepare')->andReturn($statement);
		$statement->shouldReceive('execute')->andReturn(true);
		$this->assertTrue($connection->execute(DB::PLAIN, 'QUERY'));
		$this->assertEquals('QUERY', $connection->lastQuery());
		$this->assertInternalType('array', $connection->lastQueryInfo());
		$this->assertEquals(array(
			'asObject' => true,
			'lateProperties' => false,
			'constructorArguments' => array(),
			'fetchInto' => null,
			'insertIdField' => 'id',
		), $connection->lastQueryOptions());
		$this->assertEquals(array(), $connection->lastQueryParams());
	}

	public function statementProvider()
	{
		$pdo = M::mock('stdClass');
		$statement = M::mock('stdClass');
		$pdo->shouldReceive('prepare')->andReturn($statement);
		$connection = DB::connection(array(
			'pdo' => $pdo,
			'driver' => 'mysql',
		));

		return array(
			array($connection, $statement, $pdo),
		);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testInsert($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->once();
		$statement->shouldReceive('rowCount')->once()->andReturn(1);
		$pdo->shouldReceive('lastInsertId')->once()->andReturn(1);
		$result = $connection->execute(DB::INSERT, 'QUERY');
		$this->assertEquals(array(1,1), $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testDelete($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->once();
		$statement->shouldReceive('rowCount')->once()->andReturn(1);
		$result = $connection->execute(DB::DELETE, 'QUERY');
		$this->assertEquals(1, $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testUpdate($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->once();
		$statement->shouldReceive('rowCount')->once()->andReturn(1);
		$result = $connection->execute(DB::UPDATE, 'QUERY');
		$this->assertEquals(1, $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testSelectInto($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->andReturn(true);
		$statement->shouldReceive('setFetchMode');
		$statement->shouldReceive('closeCursor');
		$statement->shouldReceive('fetch');
		$result = $connection->execute(DB::SELECT, 'QUERY', array(), array(
			'fetchInto' => new stdClass,
		));
		$this->assertInstanceOf('stdClass', $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testSelectFail($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->andReturn(false);
		$result = $connection->execute(DB::SELECT, 'QUERY');
		$this->assertFalse($result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testSelectAssoc($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->andReturn(true);
		$statement->shouldReceive('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn('result');
		$result = $connection->execute(DB::SELECT, 'QUERY :param', array(
				'param' => DB::expr('value'),
				'param2' => 'Param2',
			), array(
			'asObject' => false,
		));
		$this->assertEquals('result', $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testSelectObject($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->andReturn(true);
		$statement->shouldReceive('fetchAll')->with(PDO::FETCH_OBJ)->andReturn('result');
		$result = $connection->execute(DB::SELECT, new Query(DB::SELECT, 'QUERY'), array(), array(
			'asObject' => true,
		));
		$this->assertEquals('result', $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testSelectClass($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->andReturn(true);
		$statement->shouldReceive('fetchAll')->with(PDO::FETCH_CLASS, 'stdClass', array())->andReturn('result');
		$result = $connection->execute(DB::SELECT, 'QUERY', array(), array(
			'asObject' => 'stdClass',
		));
		$this->assertEquals('result', $result);
	}

	/**
	 * @dataProvider  statementProvider
	 */
	public function testSelectClassLate($connection, $statement, $pdo)
	{
		$statement->shouldReceive('execute')->andReturn(true);
		$statement->shouldReceive('fetchAll')->with(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'stdClass', array())->andReturn('result');
		$result = $connection->execute(DB::SELECT, 'QUERY', array(), array(
			'asObject' => 'stdClass',
			'lateProperties' => true,
		));
		$this->assertEquals('result', $result);
	}

	/**
	 * @expectedException \Fuel\Database\Exception
	 */
	public function testFailedQuery()
	{
		$connection = DB::connection(array('username' => 'root'));
		$connection->execute(DB::PLAIN, 'SOME QUERY');
	}

	public function testNonNumericInsertId()
	{
		$pdo = M::mock('stdClass');
		$pdo->shouldReceive('lastInsertId')
			->once()
			->with('id')
			->andReturn('string');
		$connection = DB::connection(array(
			'pdo' => $pdo
		));

		$this->assertEquals('string', $connection->getLastInsertId(false, array('insertIdField' => 'id')));
	}

	public function testPgSpecific()
	{

		$connection = DB::connection(array(
			'driver' => 'pgsql',
			'pdo' => M::mock('stdClass'),
			'insertIdField' => null,
		));

		$insert = $connection->insert('users');
		$compiler = $connection->getCompiler();
		$this->assertNull($compiler->compileReturning($insert));
		$this->assertNull($connection->getLastInsertId(false, array('insertIdField' => null)));
		$statement = M::mock('statement');
		$statement->shouldReceive('fetch')->once()->with(PDO::FETCH_ASSOC)->andReturn(array(
			'id' => 1
		));
		$this->assertEquals(1, $connection->getLastInsertId($statement, array('insertIdField' => 'id')));
	}

	public function testMysqlSpecific()
	{
		$connection = DB::connection(array(
			'driver' => 'mysql',
			'pdo' => M::mock('stdClass'),
		));

		$compiler = $connection->getCompiler();
		$this->assertEquals('CONCAT(`id`, " ", `name`)', $compiler->compileCommandConcat(array(
			'id', DB::expr('" "'), 'name',
		)));
	}
}
