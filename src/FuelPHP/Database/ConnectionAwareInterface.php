<?php

namespace FuelPHP\Database;

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