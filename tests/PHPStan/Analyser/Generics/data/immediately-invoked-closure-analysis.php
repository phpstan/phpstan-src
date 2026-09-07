<?php declare(strict_types = 1);

namespace ImmediatelyInvokedClosureAnalysis;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @param list<positive-int> $items */
function doFoo(array $items): void
{
	$result = (static function (array $values): array {
		assertType('list<int<1, max>>', $values);
		assertNativeType('array', $values);

		return $values;
	})($items);
	assertType('list<int<1, max>>', $result);

	$result = (static fn (array $values): array => $values)($items);
	assertType('list<int<1, max>>', $result);
	assertNativeType('array', $result);
}
