<?php

namespace PgDml;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doInsert(\PgSql\Connection $connection, int $flags): void
	{
		assertType('PgSql\Result|false', pg_insert($connection, 'table', []));
		assertType('PgSql\Result|false', pg_insert($connection, 'table', [], PGSQL_DML_EXEC));
		assertType('PgSql\Result|false', pg_insert($connection, 'table', [], PGSQL_DML_EXEC | PGSQL_DML_STRING));
		assertType('string|false', pg_insert($connection, 'table', [], PGSQL_DML_STRING));
		assertType('string|false', pg_insert($connection, 'table', [], PGSQL_DML_NO_CONV | PGSQL_DML_STRING));
		assertType('bool', pg_insert($connection, 'table', [], PGSQL_DML_ASYNC));
		assertType('bool|PgSql\Result|string', pg_insert($connection, 'table', [], $flags));
	}

	public function doUpdate(\PgSql\Connection $connection, int $flags): void
	{
		assertType('bool', pg_update($connection, 'table', [], []));
		assertType('bool', pg_update($connection, 'table', [], [], PGSQL_DML_EXEC));
		assertType('string|false', pg_update($connection, 'table', [], [], PGSQL_DML_STRING));
		assertType('string|false', pg_update($connection, 'table', [], [], PGSQL_DML_EXEC | PGSQL_DML_STRING));
		assertType('bool|string', pg_update($connection, 'table', [], [], $flags));
	}

	public function doDelete(\PgSql\Connection $connection, int $flags): void
	{
		assertType('bool', pg_delete($connection, 'table', []));
		assertType('bool', pg_delete($connection, 'table', [], PGSQL_DML_EXEC));
		assertType('string|false', pg_delete($connection, 'table', [], PGSQL_DML_STRING));
		assertType('bool|string', pg_delete($connection, 'table', [], $flags));
	}

	public function doSelect(\PgSql\Connection $connection, int $flags): void
	{
		assertType('array<int, array>|false', pg_select($connection, 'table', []));
		assertType('array<int, array>|false', pg_select($connection, 'table', [], PGSQL_DML_EXEC));
		assertType('string|false', pg_select($connection, 'table', [], PGSQL_DML_STRING));
		assertType('array<int, array>|string|false', pg_select($connection, 'table', [], $flags));
	}

}
