<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14369DeadCode;

use Exception;

function test(string|null $test): void
{
	$test ??= throw new Exception();

	echo $test;
}
