<?php // lint >= 8.1

declare(strict_types = 1);

namespace PreciseArrayShapeUnpackingLegacy;

use function PHPStan\Testing\assertType;

/**
 * @param array{a: 1}|array{b: 2} $input
 */
function test(array $input): void
{
	assertType('array{a?: 1, b?: 2}', [...$input]);
}
