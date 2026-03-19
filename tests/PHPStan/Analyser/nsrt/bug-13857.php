<?php

declare(strict_types = 1);

namespace Bug13857;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, array{state: string}> $array
 */
function test(array $array, int $id): void {
	$array[$id]['state'] = 'foo';
	// only one element was set to 'foo', not all of them.
	assertType("non-empty-array<int, array{state: string}>", $array);
}
