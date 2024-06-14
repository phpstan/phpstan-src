<?php

namespace MySQLiStmtAffectedRowsAndNumRowsTypes;

use mysqli;
use function PHPStan\Testing\assertType;

final class Foo {
	public function bar(): void
	{
		$mysqli = new mysqli();
		$stmt = $mysqli->prepare('SELECT x FROM z;');
		$stmt->execute();
		assertType('int<0, max>|numeric-string', $stmt->num_rows);

		$stmt = $mysqli->prepare('DELETE FROM z;');
		$stmt->execute();
		assertType('int<-1, max>|numeric-string', $stmt->affected_rows);
	}
}
