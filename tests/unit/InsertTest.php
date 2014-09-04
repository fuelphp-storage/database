<?php

namespace Fuel\Database;

use Codeception\TestCase\Test;
use Mockery as M;

class InsertTest extends Test
{
	public function connectionProvider()
	{
		return array(
			array(DB::connection(array(
				'driver' => 'mysql',
				'pdo' => M::mock('stdClass'),
			))),
			array(DB::connection(array(
				'driver' => 'pgsql',
				'pdo' => M::mock('stdClass'),
			))),
			array(DB::connection(array(
				'driver' => 'mysql',
				'pdo' => M::mock('sqlite'),
			))),
			array(DB::connection(array(
				'driver' => 'sqlsrv',
				'pdo' => M::mock('sqlite'),
			))),
		);
	}

	/**
	 * @dataProvider  connectionProvider
	 */
	public function testInsert($connection)
	{
		$insert = $connection->insert('input');
		$pdo = $connection->getPdo();
		$pdo->shouldReceive('quote')->andReturnUsing(function($value){
			return $value;
		});
		$insert->values(array(
			'column' => 'value',
		));
		$insert->values(array(array(
			'column' => 'replaced in merge',
		)));
		$insert->values(array(
			'column' => 'replaced value',
		), true);
		$sql = $insert->getQuery();
		$this->assertStringStartsWith('INSERT INTO', $sql);
		$this->assertStringMatchesFormat('%ainput%a', $sql);
		$this->assertStringMatchesFormat('%aVALUES%a', $sql);
		$this->assertStringMatchesFormat('%areplaced value%a', $sql);
	}
}
