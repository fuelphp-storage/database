<?php

class DBTests extends PHPUnit_Framework_TestCase
{
	public function mockConnection()
	{
		return M::mock('FuelPHP\Database\Connection');
	}

	public function expressionProvider()
	{
		return array(
			array(' value ', $this->mockConnection()),
			array(true, $this->mockConnection()),
			array(10, $this->mockConnection()),
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
		$connection->shoudReceive('quote')->once()->andReturn($string);
		echo get_class($expression);
		var_dump($expression->getValue($connection));
		$this->assertInstanceOf('FuelPHP\Database\Expression\Value', $expression);
		//$this->assertEquals($string, $expression->getValue($connection));
	}
}