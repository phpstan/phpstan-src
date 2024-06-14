<?php

namespace ConstantArrayIntersect;

use function PHPStan\Testing\assertType;

/**
 * @param array{key: string|null}&array<string> $array1
 * @param array<string>&array{key: string|null} $array2
 */
function test(
	array $array1,
	array $array2,
): void {
	assertType('array{key: string}', $array1);
	assertType('array{key: string}', $array2);
}
