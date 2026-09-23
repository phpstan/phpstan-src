<?php

namespace PgResultStatus;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(\PgSql\Result $result, int $mode): void
	{
		assertType('int', pg_result_status($result));
		assertType('int', pg_result_status($result, PGSQL_STATUS_LONG));
		assertType('string', pg_result_status($result, PGSQL_STATUS_STRING));
		assertType('int|string', pg_result_status($result, $mode));

		if ($mode === PGSQL_STATUS_LONG || $mode === PGSQL_STATUS_STRING) {
			assertType('int|string', pg_result_status($result, $mode));
		}
	}

}
