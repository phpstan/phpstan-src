<?php

namespace Bug8058;

use function PHPStan\Testing\assertType;

class Foo
{
	public function syntaxError(\mysqli $mysqli, string $s): void
	{
		$mysqli->execute_query($s);

		\mysqli_execute_query($mysqli, $s);
	}
}
