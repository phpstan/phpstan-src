<?php

namespace MySQLiResultNumRowsType;

use mysqli;
use function PHPStan\Testing\assertType;

final class Foo {
	public function bar(): void
	{
		$mysqli = new mysqli();
		$mysqliResult = $mysqli->query('SELECT x FROM z;');
		assertType('int<0, max>|numeric-string', $mysqliResult->num_rows);
	}
}
