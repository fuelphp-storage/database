<?php

class DeleteTests extends PHPUnit_Framework_TestCase
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
	public function testDelete($connection)
	{
		$delete = $connection->delete('table')->from('input');
		$sql = $delete->getQuery();
		$this->assertStringStartsWith('DELETE FROM', $sql);
		$this->assertStringMatchesFormat('%ainput%a', $sql);
	}
}