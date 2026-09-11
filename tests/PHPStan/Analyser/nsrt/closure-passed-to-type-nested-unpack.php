<?php // lint >= 8.1

declare(strict_types = 1);

namespace ClosurePassedToTypeNestedUnpack;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param array<T, \Closure(T): void> $callbacks
 */
function acceptKeyedGenericWithUnpack(array $callbacks): void {}

/** @param array<'z', \Closure('z'): void> $more */
function withUnpack(array $more): void {
	acceptKeyedGenericWithUnpack([
		...$more,
		'foo' => function ($value): void {
			assertType("'foo'|'z'", $value);
		},
	]);
};
