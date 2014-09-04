<?php

namespace Fuel\Database;

use Codeception\TestCase\Test;
use Mockery as M;

class UpdateTest extends Test
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
	public function testUpdate($connection)
	{
		$update = $connection->update('table')->table('input');
		$pdo = $connection->getPdo();
		$pdo->shouldReceive('quote')->andReturnUsing(function($value){
			return $value;
		});
		$update->set('col', 'value')->increment('age');
		$sql = $update->getQuery();
		$this->assertStringStartsWith('UPDATE', $sql);
		$this->assertStringMatchesFormat('%ainput%a', $sql);
		$this->assertStringMatchesFormat('%a SET %a', $sql);
		$this->assertStringMatchesFormat('%a + %a', $sql);
	}
}
