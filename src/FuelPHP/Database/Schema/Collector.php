<?php

namespace FuelPHP\Database\Schema;

class Collector
{
	/**
	 * @var  array  $data  field data
	 */
	protected $data = array();

	/**
	 * Isset implementation
	 *
	 * @param   string   $key  key to check
	 * @return  boolean  wether value isset
	 */
	public function __isset($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Isset implementation
	 *
	 * @param   string  $key      key to fetch
	 * @param   mixed   $default  default return value
	 * @return  mixed   value or default
	 */
	public function get($key, $default = null)
	{
		if (isset($this->data[$key]))
		{
			return $this->data[$key];
		}

		if ($default instanceof Closure)
		{
			return $default($key);
		}

		return $default;
	}

	/**
	 * __set implementation
	 *
	 * @param   string  $key      key to fetch
	 * @param   mixed   $default  default return value
	 */
	public function __set($key, $value)
	{
		return $this->data[$key] = $value;
	}

	/**
	 * Magig-method setters
	 *
	 * @param   string  $method    method name
	 * @param   mixed   $argument  arguments
	 * @return  $this
	 */
	public function __call($method, $arguments)
	{
		if (empty($arguments))
		{
			$arguments[] = true;
		}

		if (count($arguments) === 1)
		{
			$arguments = reset($arguments);
		}

		$this->{$method} = $arguments;

		return $this;
	}
}