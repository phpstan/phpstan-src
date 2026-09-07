<?php declare(strict_types = 1);

namespace ArrayMapClosureAnalysis;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @param list<positive-int> $items */
function doFoo(array $items): void
{
	$result = array_map(static function (int $value): int {
		assertType('int<1, max>', $value);
		assertNativeType('int', $value);

		return $value;
	}, $items);
	assertType('list<int<1, max>>', $result);
	assertNativeType('array', $result);

	$result = array_map(static fn (int $value): int => $value, $items);
	assertType('list<int<1, max>>', $result);
	assertNativeType('array', $result);
}
