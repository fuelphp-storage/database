<?php

namespace FuelPHP\Database\Schema\Compiler;

use FuelPHP\Database\Schema\Field;
use FuelPHP\Database\Schema\Table;
use FuelPHP\Database\Schema\Collector;
use FuelPHP\Database\Schema\Compiler;

class Pgsql extends Compiler
{
	public function compileComment(Collector $collector)
	{
		return '';
	}

	public function compileFieldInteger(Field $field)
	{
		if ($field->get('increment'))
		{
			return 'SERIAL ';
		}

		return 'INT';
	}

	public function compileCreateTable(Table $table)
	{
		$queries = (array) parent::compileCreateTable($table);

		if ($tableComment = $this->compileTableCommentSql($table))
		{
			$queries[] = $tableComment;
		}

		array_merge($queries, $this->compileFieldsCommentSql($table));

		return $queries;
	}

	public function compileTableCommentSql(Table $table)
	{
		if ($comment = $table->get('comment') or $comment = $table->get('comments'))
		{
			$sql = 'COMMENT ON TABLE '.$this->quoteIdentifier($table->table).
				' IS '.$this->quote($comment);

			return $sql;
		}
	}

	public function compileFieldsCommentSql(Table $table)
	{
		$queries = array();

		foreach ($table->fields as $field)
		{
			if ($comment = $table->get('comment') or $comment = $table->get('comments'))
			{
				$field = $field->get('name') ?: $field->field;

				$queries[] = 'COMMENT ON COLUMN '.$this->quoteIdentifier($table->table.'.'.$field).
					' IS '.$this->quote($comment);
			}
		}

		return $queries;
	}

	public function compileListTables()
	{
		return "SELECT table_name FROM information_schema.tables".
				" WHERE table_type = 'BASE TABLE' AND table_schema NOT IN".
				" ('pg_catalog', 'information_schema')";
	}

	public function normalizeFields(array $fields)
	{
		$defaults = $this->getFieldDefaults();

		return array_map(function($field) use($defaults)
		{
			$normalized = $defaults;

			return $field;
			return $normalized;
		},
		$fields);
	}

	public function compileListFields($table)
	{
		return "SELECT
					a.attname AS field,
					t.typname AS type,
					format_type(a.atttypid, a.atttypmod) AS complete_type,
					(SELECT t1.typname FROM pg_catalog.pg_type t1 WHERE t1.oid = t.typbasetype) AS domain_type,
					(SELECT format_type(t2.typbasetype, t2.typtypmod) FROM pg_catalog.pg_type t2
						WHERE t2.typtype = 'd' AND t2.typname = format_type(a.atttypid, a.atttypmod)) AS domain_complete_type,
						a.attnotnull AS isnotnull,
					(SELECT 't'
						FROM pg_index
						WHERE c.oid = pg_index.indrelid
						AND pg_index.indkey[0] = a.attnum
						AND pg_index.indisprimary = 't'
					) AS pri,
					(SELECT pg_attrdef.adsrc
						FROM pg_attrdef
						WHERE c.oid = pg_attrdef.adrelid
						AND pg_attrdef.adnum=a.attnum
					) AS default,
					(SELECT pg_description.description
						FROM pg_description WHERE pg_description.objoid = c.oid AND a.attnum = pg_description.objsubid
					) AS comment
					FROM pg_attribute a, pg_class c, pg_type t, pg_namespace n
					WHERE ".$this->compileTableWhereClause($table, 'c', 'n') ."
						AND a.attnum > 0
						AND a.attrelid = c.oid
						AND a.atttypid = t.oid
						AND n.oid = c.relnamespace
					ORDER BY a.attnum";
	}

	/**
	 * @param string $table
	 * @param string $classAlias
	 * @param string $namespaceAlias
	 *
	 * COPIED FROM Doctrine\DBAL
	 *
	 * @return string
	 */
	private function compileTableWhereClause($table, $classAlias = 'c', $namespaceAlias = 'n')
	{
		$whereClause = $namespaceAlias.".nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast') AND ";
		if (strpos($table, ".") !== false) {
			list($schema, $table) = explode(".", $table);
			$schema = "'" . $schema . "'";
		} else {
			$schema = "ANY(string_to_array((select setting from pg_catalog.pg_settings where name = 'search_path'),','))";
		}
		$whereClause .= "$classAlias.relname = '" . $table . "' AND $namespaceAlias.nspname = $schema";

		return $whereClause;
	}
}