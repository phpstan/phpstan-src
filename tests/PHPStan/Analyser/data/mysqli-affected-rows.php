<?php

namespace MySqlAffectedRowsType;

use mysqli;
use function PHPStan\Testing\assertType;

final class Foo {
	public function bar(): void
	{
		$mysqli = new mysqli();
		$mysqli->query('UPDATE x SET y = 0;');
		assertType('int<-1, max>|numeric-string', $mysqli->affected_rows);
	}
}
