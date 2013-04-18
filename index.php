<?php

include 'vendor/autoload.php';

use FuelPHP\Database\DB;

class Dummy
{
	public $_constructed = false;
	public $_before = array();
	public $_after = array();
	public $_arguments = array();

	public function __construct()
	{
		$this->_constructed = true;
		$this->_arguments = func_get_args();
	}

	public function __set($key, $value)
	{
		if ($this->_constructed)
		{
			$this->_after[$key] = $value;
		}
		else
		{
			$this->_before[$key] = $value;
		}
	}
};

$connection = DB::connection(array(
	'driver' => 'mysql',
	'username' => 'root',
	'password' => 'root',
	'database' => 'ku_cms',
));

$schema = $connection->getSchema();

print_r($schema->createTable('new_table', function($table) {
	$table->integer('id')->increment();
	$table->string('name', 250)->null();
	$table->string('surname', 250)->null();
	$table->engine('MyISAM');
}));

print_r($schema->alterTable('some_table', function($table) {
	$table->string('something', 20);
	$table->boolean('boelala', true);
	$table->string('lala', 20)->default('wow');
	$table->change('uid')->default(11);
	$table->drop('uid');
	$table->rename('name', 'other_name');
}));

print_r($schema->dropTable('some_table'));
// $platform = $doctrineSchema->getDatabasePlatform();
// $schema = $doctrineSchema->createSchema();
// $table = $schema->getTable('some_table');
// $newSchema = clone $schema;
// $new = $newSchema->getTable('some_table');
// $new->addColumn('new_column', 'string', array('length' => 12));
// $comp = new \Doctrine\DBAL\Schema\Comparator();
// $diff = $comp->compare($schema, $newSchema);
// print_r(get_class_methods($diff));
// //$diff = $comp->compare($newSchema, $schema);
// print_r($diff->toSql($platform));
// die();

// foreach($doctrineSchema->listTableColumns('some_table') as $column)
// {
// 	$s = serialize($column);
// 	echo $s;
// 	$u = unserialize($s);
// 	echo $u->getName();
// }

die();

$schema->dropTable('some_table', true);

$schema->createTable('some_table', function($table) {
	$table->varchar('name', 20)
		->nullable()
		->comment('A comment is places')
		->default('This value')
		->charset('utf8_unicode_ci');

	$table->integer('id', 11)->increment();
	$table->integer('uid', 11)->default(12);
});

$schema = $connection->getSchema();
var_dump($schema->listFields('some_table'));
die();

//print_r($connection->lastQuery());

$connection->insert('some_table')->values(array(
	'name' => 'One',
))->execute();

$object = new stdClass;

$connection->select()
	->from('some_table')
	->fetchInto($object)
	->execute();

print_r($object);

die();

$connection->insert('menu')
	->values(array('position' => 2))
	->execute();

$connection->update('menu')
	->set('position', 3)
	->where('position', 2)
	->execute();

$result = $connection->select()
	->from('menu')
	->asObject('Dummy')
	->orderBy('id', 'desc')
	->lateProperties()
	->withArguments(array('woo' => 22))
	->execute();

$connection->delete('menu')
	->whereArray(array(
		'position' => array('!=', '?'),
		'id' => array('!=', '?')
	))
	->execute(array(
		1,
		1
	));

print_r($connection->lastQuery());