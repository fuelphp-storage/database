<?php

class DBTests extends PHPUnit_Framework_TestCase
{
	public function mockConnection()
	{
		return M::mock('FuelPHP\Database\Connection');
	}

	public function mockCompiler()
	{
		return M::mock('FuelPHP\Database\Compiler');
	}

	public function expressionProvider()
	{
		return array(
			array(' value ', $this->mockConnection()),
			array(true, $this->mockConnection()),
			array(10, $this->mockConnection()),
		);
	}

	public function paramProvider()
	{
		return array(
			array('name', ':name', $this->mockConnection()),
			array('thing', ':thing', $this->mockConnection()),
			array('yes', ':yes', $this->mockConnection()),
		);
	}

	public function incrementProvider()
	{
		return array(
			array('name', 1, '`name` + 1', $this->mockConnection(), $this->mockCompiler()),
			array('name', 10, '`name` + 10', $this->mockConnection(), $this->mockCompiler()),
			array('name', -1, '`name` - 1', $this->mockConnection(), $this->mockCompiler()),
			array('name', -10, '`name` - 10', $this->mockConnection(), $this->mockCompiler()),
		);
	}

	public function commandProvider()
	{
		return array(
			array('something', array(), 'alias', null, 'SOMETHING() AS alias', $this->mockConnection(), $this->mockCompiler()),
			array('something', array(), null, null, 'SOMETHING()', $this->mockConnection(), $this->mockCompiler()),
			array('something', array(null), null, null, 'SOMETHING(NULL)', $this->mockConnection(), $this->mockCompiler()),
			array('concat', array('field', 'field'), null, 'compileCommandConcat', '`field` || `field`', $this->mockConnection(), $this->mockCompiler()),
		);
	}

	public function factoryProvider()
	{
		return array(
			array(
				'query',
				array('SELECT * FROM `users`'),
				'FuelPHP\Database\Query',
			),
			array(
				'connection',
				array(array('autoConnect' => false, 'driver' => 'mysql')),
				'FuelPHP\Database\Connection',
			),
			array(
				'select',
				array('something'),
				'FuelPHP\Database\Collector\Select',
			),
			array(
				'selectArray',
				array(array('something')),
				'FuelPHP\Database\Collector\Select',
			),
			array(
				'update',
				array('something'),
				'FuelPHP\Database\Collector\Update',
			),
			array(
				'insert',
				array('something'),
				'FuelPHP\Database\Collector\Insert',
			),
			array(
				'delete',
				array('something'),
				'FuelPHP\Database\Collector\Delete',
			),
		);
	}

	/**
     * @dataProvider expressionProvider
     */
	public function testExpr($string, $connection)
	{
		$expression = DB::expr($string);
		$this->assertInstanceOf('FuelPHP\Database\Expression', $expression);
		$this->assertEquals($string, $expression->getValue($connection));
	}

	/**
     * @dataProvider expressionProvider
     */
	public function testExprValue($string, $connection)
	{
		$expression = DB::value($string);
		$connection->shouldReceive('quote')->with($string)->once()->andReturn($string);
		$this->assertInstanceOf('FuelPHP\Database\Expression\Value', $expression);
		$this->assertEquals($string, $expression->getValue($connection));
	}

	/**
     * @dataProvider expressionProvider
     */
	public function testExprIdentifier($string, $connection)
	{
		$expression = DB::identifier($string);
		$connection->shouldReceive('quoteIdentifier')->with($string)->once()->andReturn($string);
		$this->assertInstanceOf('FuelPHP\Database\Expression\Identifier', $expression);
		$this->assertEquals($string, $expression->getValue($connection));
	}

	/**
     * @dataProvider paramProvider
     */
	public function testExprParameter($string, $result, $connection)
	{
		$expression = DB::param($string);
		$this->assertInstanceOf('FuelPHP\Database\Expression\Parameter', $expression);
		$this->assertEquals($result, $expression->getValue($connection));
	}

	/**
     * @dataProvider incrementProvider
     */
	public function testExprIncrement($string, $amount, $result, $connection, $compiler)
	{
		$expression = DB::increment($string, $amount);
		$compiler->shouldReceive('compileIncrement')->once()->andReturn($result);
		$connection->shouldReceive('getCompiler')->once()->andReturn($compiler);
		$this->assertInstanceOf('FuelPHP\Database\Expression\Increment', $expression);
		$this->assertEquals($result, $expression->getValue($connection));
	}

	/**
     * @dataProvider commandProvider
     */
	public function testExprCommand($name, $params, $alias, $expectedCall, $result, $connection, $compiler)
	{
		$expression = DB::command($name);
		$expression->arguments = $params;
		$expression->alias($alias);
		$this->assertInstanceOf('FuelPHP\Database\Expression\Command', $expression);
		if ($expectedCall)
		{
			$compiler->shouldReceive($expectedCall)->once()->andReturn($result);
		}
		elseif ( ! empty($params))
		{
			$connection->shouldReceive('quote')->with($params)->andReturn('NULL');
		}
		$connection->shouldReceive('getCompiler')->once()->andReturn($compiler);
		$this->assertEquals($result, $expression->getValue($connection));
	}

	public function testExprConcat()
	{
		$pdo = M::mock('stdclass');
		$expression = DB::command('concat', 'some', 'fields');
		$connection = DB::connection(array(
			'pdo' => $pdo,
			'driver' => 'pgsql',
		));
		$sql = $expression->getValue($connection);
		$this->assertEquals('"some" || "fields"', $sql);
	}

	public function testExprWhen()
	{
		$expression = DB::when('field');
		$expression->is('this', 'that');
		$expression->is('what', 'oink');
		$expression->orElse('no');
		$pdo = M::mock('stdclass');
		$connection = DB::connection(array(
			'pdo' => $pdo
		));
		$pdo->shouldReceive('quote')->andReturnUsing(function($value) {
			return '"'.$value.'"';
		});
		$sql = $expression->getValue($connection);
		$this->assertEquals('CASE `field` WHEN "this" THEN "that" WHEN "what" THEN "oink" ELSE "no" END', $sql);
	}

	public function testExprMatch()
	{
		$expression = DB::match('age')->against('this');
		$pdo = M::mock('stdclass');
		$connection = DB::connection(array(
			'pdo' => $pdo
		));
		$pdo->shouldReceive('quote')->andReturnUsing(function($value) {
			return '"'.$value.'"';
		});
		$sql = $expression->getValue($connection);
		$this->assertEquals('MATCH (`age`) AGAINST ("this")', $sql);
		$expression->boolean();
		$sql = $expression->getValue($connection);
		$this->assertEquals('MATCH (`age`) AGAINST ("this" IN BOOLEAN MODE)', $sql);
		$expression->expand();
		$sql = $expression->getValue($connection);
		$this->assertEquals('MATCH (`age`) AGAINST ("this" WITH QUERY EXPANSION)', $sql);
		$expression->boolean();
		$sql = $expression->getValue($connection);
		$this->assertEquals('MATCH (`age`) AGAINST ("this" IN BOOLEAN MODE)', $sql);
	}

	/**
     * @dataProvider factoryProvider
     */
	public function testFactoryMethods($method, $arguments, $class)
	{
		$result = call_user_func_array('DB::'.$method, $arguments);

		$this->assertInstanceOf($class, $result);
	}

	/**
	 * @expectedException FuelPHP\Database\Exception
	 */
	public function testInvalidConnection()
	{
		DB::connection(array(
			'driver' => 'unknown',
		));
	}
}