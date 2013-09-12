<?php

class QueryTests extends PHPUnit_Framework_TestCase
{
	public function methodProvider()
	{
		return array(
			array(
				'withArguments',
				array('this'),
				'constructorArguments'
			),
			array(
				'lateProperties',
				true,
				'lateProperties'
			),
			array(
				'fetchInto',
				new stdClass,
				'fetchInto'
			),
			array(
				'asObject',
				'Something',
				'asObject'
			),
			array(
				'asObject',
				null,
				'asObject'
			),
			array(
				'asObject',
				true,
				'asObject'
			),
			array(
				'asAssoc',
				false,
				'asObject'
			),
		);
	}

	public function typeProvider()
	{
		return array(
			array(DB::PLAIN),
			array(DB::UPDATE),
			array(DB::DELETE),
		);
	}

	/**
	 * @dataProvider  methodProvider
	 */
	public function testOptionMethods($method, $argument, $property)
	{
		$query = DB::query('STATEMENT');
		call_user_func_array(array($query, $method), array(&$argument));
		$this->assertAttributeEquals($argument, $property, $query);
	}

	/**
	 * @dataProvider  typeProvider
	 */
	public function testQueryType($type)
	{
		$query = new Query('STATEMENT', $type);
		$this->assertEquals($type, $query->type);
	}

	public function testParams()
	{
		$query = DB::query('LOL');
		$query->setParam('this', 'that');
		$param = 1;
		$query->bindParam('bound', $param);
		$param++;
		$query->setParams(array(
			'other' => 'param',
		));
		$params = $query->getParams();

		$this->assertEquals(array(
			'this' => 'that',
			'bound' => 2,
			'other' => 'param',
		), $params);
	}

	public function testSetConnection()
	{
		$connection = M::mock('Fuel\Database\Connection');
		$compiler = M::mock('Fuel\Database\Compiler');
		$connection->shouldReceive('getCompiler')->once()->andReturn($compiler);
		$query = new Query('STUFF');
		$query->setConnection($connection);
	}

	public function testCompile()
	{
		$query = new Query('This');
		$this->assertEquals('This', $query->getQuery());
	}

	public function testQueryOptions()
	{
		$connection = DB::connection(array(
			'driver' => 'mysql',
			'autoConnect' => false,
		));

		$query = new Query('Stuff');
		$query->setConnection($connection);

		$options = $query->getOptions();
		$this->assertEquals(array(
			'asObject' => true,
			'lateProperties' => false,
			'constructorArguments' => array(),
			'fetchInto' => null,
			'resultCollection' => null,
			'insertIdField' => 'id',
		), $options);
	}

	public function testInsertIdField()
	{
		$query = new Query('yeah');
		$this->assertNull($query->getInsertIdField());
		$query->insertIdField('id');
		$this->assertEquals('id', $query->getInsertIdField());
	}

	public function testExecute()
	{
		$connection = M::mock('Fuel\Database\Connection');
		$connection->shouldReceive('execute')->once()->andReturn('result');
		$query = new Query('');
		$this->assertEquals('result', $query->execute(array(), $connection));
	}

	/**
	 * @expectedException Fuel\Database\Exception
	 */
	public function testInvalidConnectionExecute()
	{
		$query = new Query('');
		$query->execute();
	}

	/**
	 * @expectedException Fuel\Database\Exception
	 */
	public function testNoConnection()
	{
		$query = new Collector\Select;
		$query->getQuery();
	}
}