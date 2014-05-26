<?php

namespace Fuel\Database;

interface ConnectionAwareInterface
{
	/**
	 * Set the connection
	 *
	 * @param   Connection  $connection  connection object
	 * @return  $this
	 */
	public function setConnection(Connection $connection);
}