<?php

class SchemaTests extends PHPUnit_Framework_TestCase
{
	public function testGetPlatform()
	{
		$connection = M::mock('Fuel\Database\Connection');
		$connection->shouldReceive('getSchemaManager')->andReturn(M::self());
		$connection->shouldReceive('getDatabasePlatform')->andReturn('called');
		$schema = new Schema($connection);
		$this->assertEquals('called', $schema->getPlatform());
	}

	public function testGetSchema()
	{
		$connection = M::mock('Fuel\Database\Connection');
		$connection->shouldReceive('getSchemaManager')->andReturn(M::self());
		$connection->shouldReceive('createSchema')->andReturn('called');
		$schema = new Schema($connection);
		$this->assertEquals('called', $schema->getSchema());
	}

	public function connectionProvider()
	{
		$connection = DB::connection(array(
			'database' => 'fuelphp_database_tests',
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'persistent' => true,
		));

		return array(
			array($connection)
		);
	}

	/**
	 * @dataProvider  connectionProvider
	 * @expectedException  BadMethodCallException
	 */
	public function testInvalidTableCall($connection)
	{
		$schema = $connection->getSchema();
		$schema->createTable('test_table', function($table){
			$table->invalidCall();
		});
	}

	/**
	 * @dataProvider  connectionProvider
	 * @expectedException  BadMethodCallException
	 */
	public function testInvalidColumnCall($connection)
	{
		$schema = $connection->getSchema();
		$schema->createTable('test_table', function($table){
			$table->string('name')->invalidCall();
		});
	}

	/**
	 * @dataProvider  connectionProvider
	 */
	public function testCreateTable($connection)
	{
		$schema = $connection->getSchema();
		$schema->createTable('test_table', function($table){
			$table->increment('id', 255)->primary();
			$table->string('name', 255)->unique();
			$table->string('surname', 255)->null()->index();
			$table->bool('bool')->index('my_index');
			$table->text('bio');
			$table->decimal('decim', 1,1);
			$table->float('floa', 1,1);
			$table->engine('MyISAM')->charset('utf8')->collate('utf8_general_ci');
			$table->fulltext(array('name', 'surname'), 'name_fulltext');
		});

		$this->assertTrue($schema->getTable('test_table')->hasIndex('name_fulltext'));
		$this->assertTrue($schema->hasTable('test_table'));
		$this->assertTrue($schema->hasColumn('test_table', 'id'));
		$this->assertFalse($schema->hasColumn('test_table', 'uid'));

		$name = $schema->getColumn('test_table', 'name');
		$bio = $schema->getColumn('test_table', 'bio');
		$this->assertEquals('bio', (string) $bio);
		$this->assertEquals('test_table', (string) $schema->getTable('test_table'));
		$this->assertEquals('String', (string) $name->getType());
		$this->assertEquals('Text', (string) $bio->getType());
		$this->assertEquals(255, $name->getLength());
		$this->assertInternalType('array', $schema->getTable('test_table')->getOptions());

		$schema->alterTable('test_table', function($table) {
			$table->drop('bio');
			$table->change('name')->length(200);
			$table->rename('bool', 'bool_renamed');
			$table->dropIndex('my_index');
			$table->changeColumn('name', array('default' => 'something'));
		});
		$name = $schema->getColumn('test_table', 'name');
		$this->assertEquals(200, $name->getLength());
		$this->assertFalse($schema->hasColumn('test_table', 'bio'));
		$this->assertInstanceOf('Doctrine\DBAL\Schema\Table', $schema->tableDetails('test_table'));
		$schema->dropTable('test_table');
		$this->assertFalse($schema->hasTable('test_table'));
	}
}