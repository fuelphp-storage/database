<?php

namespace Fuel\Database;

use Codeception\TestCase\Test;
use Mockery as M;

class DBTest extends Test
{
	public function mockConnection()
	{
		return M::mock('Fuel\Database\Connection');
	}

	public function mockCompiler()
	{
		return M::mock('Fuel\Database\Compiler');
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
				'Fuel\Database\Query',
			),
			array(
				'connection',
				array(array('autoConnect' => false, 'driver' => 'mysql')),
				'Fuel\Database\Connection',
			),
			array(
				'select',
				array('something'),
				'Fuel\Database\Collector\Select',
			),
			array(
				'selectArray',
				array(array('something')),
				'Fuel\Database\Collector\Select',
			),
			array(
				'update',
				array('something'),
				'Fuel\Database\Collector\Update',
			),
			array(
				'insert',
				array('something'),
				'Fuel\Database\Collector\Insert',
			),
			array(
				'delete',
				array('something'),
				'Fuel\Database\Collector\Delete',
			),
		);
	}

	/**
     * @dataProvider expressionProvider
     */
	public function testExpr($string, $connection)
	{
		$expression = DB::expr($string);
		$this->assertInstanceOf('Fuel\Database\Expression', $expression);
		$this->assertEquals($string, $expression->getValue($connection));
	}

	/**
     * @dataProvider expressionProvider
     */
	public function testExprValue($string, $connection)
	{
		$expression = DB::value($string);
		$connection->shouldReceive('quote')->with($string)->once()->andReturn($string);
		$this->assertInstanceOf('Fuel\Database\Expression\Value', $expression);
		$this->assertEquals($string, $expression->getValue($connection));
	}

	/**
     * @dataProvider expressionProvider
     */
	public function testExprIdentifier($string, $connection)
	{
		$expression = DB::identifier($string);
		$connection->shouldReceive('quoteIdentifier')->with($string)->once()->andReturn($string);
		$this->assertInstanceOf('Fuel\Database\Expression\Identifier', $expression);
		$this->assertEquals($string, $expression->getValue($connection));
	}

	/**
     * @dataProvider paramProvider
     */
	public function testExprParameter($string, $result, $connection)
	{
		$expression = DB::param($string);
		$this->assertInstanceOf('Fuel\Database\Expression\Parameter', $expression);
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
		$this->assertInstanceOf('Fuel\Database\Expression\Increment', $expression);
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
		$this->assertInstanceOf('Fuel\Database\Expression\Command', $expression);
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
	 * @expectedException \Fuel\Database\Exception
	 */
	public function testInvalidConnection()
	{
		DB::connection(array(
			'driver' => 'unknown',
		));
	}
}
