<?php // lint < 8.1

namespace PgDmlResource;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param resource $connection
	 */
	public function doInsert($connection, int $flags): void
	{
		assertType('resource|false', pg_insert($connection, 'table', []));
		assertType('resource|false', pg_insert($connection, 'table', [], PGSQL_DML_EXEC));
		assertType('resource|false', pg_insert($connection, 'table', [], PGSQL_DML_EXEC | PGSQL_DML_STRING));
		assertType('string|false', pg_insert($connection, 'table', [], PGSQL_DML_STRING));
		assertType('string|false', pg_insert($connection, 'table', [], PGSQL_DML_NO_CONV | PGSQL_DML_STRING));
		assertType('bool', pg_insert($connection, 'table', [], PGSQL_DML_ASYNC));
		assertType('bool|resource|string', pg_insert($connection, 'table', [], $flags));
	}

	/**
	 * @param resource $connection
	 */
	public function doUpdate($connection, int $flags): void
	{
		assertType('bool', pg_update($connection, 'table', [], []));
		assertType('bool', pg_update($connection, 'table', [], [], PGSQL_DML_EXEC));
		assertType('string|false', pg_update($connection, 'table', [], [], PGSQL_DML_STRING));
		assertType('string|false', pg_update($connection, 'table', [], [], PGSQL_DML_EXEC | PGSQL_DML_STRING));
		assertType('bool|string', pg_update($connection, 'table', [], [], $flags));
	}

	/**
	 * @param resource $connection
	 */
	public function doDelete($connection, int $flags): void
	{
		assertType('bool', pg_delete($connection, 'table', []));
		assertType('bool', pg_delete($connection, 'table', [], PGSQL_DML_EXEC));
		assertType('string|false', pg_delete($connection, 'table', [], PGSQL_DML_STRING));
		assertType('bool|string', pg_delete($connection, 'table', [], $flags));
	}

	/**
	 * @param resource $connection
	 */
	public function doSelect($connection, int $flags): void
	{
		assertType('array<int, array>|false', pg_select($connection, 'table', []));
		assertType('array<int, array>|false', pg_select($connection, 'table', [], PGSQL_DML_EXEC));
		assertType('string|false', pg_select($connection, 'table', [], PGSQL_DML_STRING));
		assertType('array<int, array>|string|false', pg_select($connection, 'table', [], $flags));
	}

}
