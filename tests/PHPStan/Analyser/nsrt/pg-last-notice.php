<?php

namespace PgLastNotice;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param mixed $connection
	 */
	public function doFoo($connection, int $mode): void
	{
		assertType('string', pg_last_notice($connection));
		assertType('string', pg_last_notice($connection, PGSQL_NOTICE_LAST));
		assertType('list<string>', pg_last_notice($connection, PGSQL_NOTICE_ALL));
		assertType('true', pg_last_notice($connection, PGSQL_NOTICE_CLEAR));
		assertType('list<string>|string|true', pg_last_notice($connection, $mode));
		assertType('list<string>|string|true', pg_last_notice($connection, 42));

		if ($mode === PGSQL_NOTICE_LAST || $mode === PGSQL_NOTICE_ALL) {
			assertType('list<string>|string', pg_last_notice($connection, $mode));
		}
	}

}
