<?php

class SchemaTests extends PHPUnit_Framework_TestCase
{
	public function testGetPlatform()
	{
		$connection = M::mock('FuelPHP\Database\Connection');
		$connection->shouldReceive('getDoctrineSchema')->andReturn(M::self());
		$connection->shouldReceive('getDatabasePlatform')->andReturn('called');
		$schema = new Schema($connection);
		$this->assertEquals('called', $schema->getPlatform());
	}

	public function testGetSchema()
	{
		$connection = M::mock('FuelPHP\Database\Connection');
		$connection->shouldReceive('getDoctrineSchema')->andReturn(M::self());
		$connection->shouldReceive('createSchema')->andReturn('called');
		$schema = new Schema($connection);
		$this->assertEquals('called', $schema->getSchema());
	}
}