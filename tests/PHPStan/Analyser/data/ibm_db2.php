<?php

namespace IBMDB2;

use function db2_autocommit;
use function db2_connect;
use function PHPStan\Testing\assertType;

use const DB2_AUTOCOMMIT_OFF;
use const DB2_AUTOCOMMIT_ON;

final class IBMDB2
{
	public function testAutocommit(): void
	{
		assertType('0|1', db2_autocommit(db2_connect()));
		assertType('bool', db2_autocommit(db2_connect(), DB2_AUTOCOMMIT_OFF));
		assertType('bool', db2_autocommit(db2_connect(), DB2_AUTOCOMMIT_ON));
	}
}
