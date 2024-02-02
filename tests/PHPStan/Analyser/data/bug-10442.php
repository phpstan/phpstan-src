<?php

namespace Bug10442;

use function PHPStan\Testing\assertType;

/**
 * @param callable(mixed): string|callable(mixed): int $callable
 */
function test(callable $callable): void
{
	$val = array_map($callable, ['val', 'val2']);

	assertType('array{int|string, int|string}', $val);
}
