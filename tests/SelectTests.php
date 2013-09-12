<?php

class SelectTests extends PHPUnit_Framework_TestCase
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
	public function testSelect($connection)
	{
		$select = $connection->select('input');
		$pdo = $connection->getPdo();
		$pdo->shouldReceive('quote')->andReturnUsing(function($value) {
			return '\''.$value.'\'';
		});
		$format = 'SELECT %a';

		$select->select('some', 'fields', $connection->select('id')->from('other_table'));
		$format .= 'some%afield%a(SELECT%a) ';

		$select->from(array('table', 'table_alias'));
		$format .= 'FROM %atable%a AS %atable_alias%a';

		$select->join('joined_table')->on('a.a', 'b.b');
		$format .= 'JOIN %ajoined_table%a ON %aa%a.%aa%a = %ab%a.%ab%a';

		$select->where('some', 'this');
		$format .= 'WHERE %asome%a = %athis%a';

		$sql = $select->getQuery();
		$this->assertStringMatchesFormat($format, $sql);
	}

	public function testWheres()
	{
		$connection = DB::connection(array(
			'driver' => 'mysql',
			'pdo' => M::mock('mysql'),
		));

		$connection->getPdo()->shouldReceive('quote')->andReturnUsing(function($value){
			return '"'.$value.'"';
		});

		$select = $connection->select('name')->from('users')->distinct();
		$expected = 'SELECT DISTINCT `name` FROM `users`';
		$this->assertEquals($expected, $select->getQuery());

		$select = $connection->select('*', 'COUNT("id")', $connection->expr('NOW()'))
			->from('users', 'comments');
		$expected = 'SELECT *, COUNT(`id`), NOW() FROM `users`, `comments`';
		$this->assertEquals($expected, $select->getQuery());

		$select->join('relation')->on('a', 'b')->andOn('a', '<', 'b')->orOn('b', 'a');
		$expected .= ' JOIN `relation` ON (`a` = `b` AND `a` < `b` OR `b` = `a`)';
		$this->assertEquals($expected, $select->getQuery());

		$select->where(function($query){
			$query->where(function($query){
				$query->where('between', 'between', array(1,3));
			});
		});
		$expected .= ' WHERE ((`between` BETWEEN 1 AND 3))';
		$this->assertEquals($expected, $select->getQuery());

		$select->where(function($query){
			$query->notWhere(function($query){
				$query->where('between', 'between', array(1,3));
			});
		});
		$expected .= ' AND (NOT (`between` BETWEEN 1 AND 3))';
		$this->assertEquals($expected, $select->getQuery());

		$select->andWhere('num', '=', null);
		$expected .= ' AND `num` IS NULL';
		$this->assertEquals($expected, $select->getQuery());

		$select->orWhereArray(array('num' => array('in', array(1,2,3.1))));
		$expected .= ' OR `num` IN (1, 2, 3.1)';
		$this->assertEquals($expected, $select->getQuery());

		$select->orWhere(function($query){
			$query->andWhereArray(array('a' => 'b'))->where('a', '!=', null)->orWhere('a', 2);
		});
		$expected .= ' OR (`a` = "b" AND `a` IS NOT NULL OR `a` = 2)';
		$this->assertEquals($expected, $select->getQuery());

		$select->notWhereArray(array('a' => 3));
		$expected .= ' AND NOT `a` = 3';
		$this->assertEquals($expected, $select->getQuery());

		$select->andNotWhereArray(array('a' => 3));
		$expected .= ' AND NOT `a` = 3';
		$this->assertEquals($expected, $select->getQuery());

		$select->andNotWhere(function($query){
			$query->where('a', 3);
		});
		$expected .= ' AND NOT (`a` = 3)';
		$this->assertEquals($expected, $select->getQuery());

		$select->orNotWhere(function($query){
			$query->orNotWhereArray(array('a' => 3));
		});
		$expected .= ' OR NOT (NOT `a` = 3)';
		$this->assertEquals($expected, $select->getQuery());

		$select->whereOpen()->where('a', 1)->orWhereClose();
		$expected .= ' AND (`a` = 1)';
		$this->assertEquals($expected, $select->getQuery());

		$select->notWhereOpen()->where('a', 1)->notWhereClose();
		$expected .= ' AND NOT (`a` = 1)';
		$this->assertEquals($expected, $select->getQuery());

		$select->notWhereOpen()->where('a', 1)->andNotWhereClose();
		$expected .= ' AND NOT (`a` = 1)';
		$this->assertEquals($expected, $select->getQuery());

		$select->notWhereOpen()->where('a', 1)->andWhereClose();
		$expected .= ' AND NOT (`a` = 1)';
		$this->assertEquals($expected, $select->getQuery());

		$select->notWhereOpen()->where('a', 1)->orNotWhereClose();
		$expected .= ' AND NOT (`a` = 1)';
		$this->assertEquals($expected, $select->getQuery());

		$select->groupBy('a', 'b')->groupBy('c', 'b');
		$expected .= ' GROUP BY `a`, `b`, `c`';
		$this->assertEquals($expected, $select->getQuery());

		$select->having('a', 'b')->andNotHaving('a', '<', '?');
		$expected .= ' HAVING `a` = "b" AND NOT `a` < ?';
		$this->assertEquals($expected, $select->getQuery());

		$select->notHaving('a', 2)->orNotHaving('b', 3);
		$expected .= ' AND NOT `a` = 2 OR NOT `b` = 3';
		$this->assertEquals($expected, $select->getQuery());

		$select->orderBy('a')->orderBy(array(
			'b' => 'desc',
			'c' => 'asc',
			'd'
		));
		$expected .= ' ORDER BY `a`, `b` DESC, `c` ASC, `d`';
		$this->assertEquals($expected, $select->getQuery());

		$select->limit(1)->offset(1);
		$expected .= ' LIMIT 1 OFFSET 1';
		$this->assertEquals($expected, $select->getQuery());
	}

	/**
	 * @expectedException  Fuel\Database\Exception
	 */
	public function testInvalidOn()
	{
		$connection = DB::connection(array(
			'driver' => 'mysql',
			'pdo' => M::mock('mysql'),
		));

		$connection->select()->on('a', 'b');
	}

	/**
	 * @expectedException  Fuel\Database\Exception
	 */
	public function testInvalidAndOn()
	{
		$connection = DB::connection(array(
			'driver' => 'mysql',
			'pdo' => M::mock('mysql'),
		));

		$connection->select()->andOn('a', 'b');
	}

	/**
	 * @expectedException  Fuel\Database\Exception
	 */
	public function testInvalidOrOn()
	{
		$connection = DB::connection(array(
			'driver' => 'mysql',
			'pdo' => M::mock('mysql'),
		));

		$connection->select()->orOn('a', 'b');
	}
}