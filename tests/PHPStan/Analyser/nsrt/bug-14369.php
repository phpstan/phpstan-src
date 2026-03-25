<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14369;

use Exception;
use function PHPStan\Testing\assertType;

function test(string|null $test): void
{
	$test ??= throw new Exception();

	assertType('string', $test);
}
