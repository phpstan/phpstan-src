<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11176;

use function PHPStan\Testing\assertType;

/** @param int|int[]|array{module: string} $arr */
function test(int|array $arr): void
{
	assertType('array<int>|array{module: string}|int', $arr);
}
