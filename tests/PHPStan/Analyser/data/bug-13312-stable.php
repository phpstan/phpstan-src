<?php declare(strict_types = 1);

namespace Bug13312Stable;

use function PHPStan\Testing\assertType;

// With polluteScopeWithAlwaysIterableForeach off and narrowForeachBodyNonEmpty off
// (the stable default), the foreach body does not narrow the iterated expression.
// The for-loop narrowing is a separate mechanism and still applies.

/** @param list<mixed> $arr */
function foo(array $arr): void {
	assertType('list<mixed>', $arr);
	foreach ($arr as $v) {
		assertType('list<mixed>', $arr);
	}
	assertType('list<mixed>', $arr);

	for ($i = 0; $i < count($arr); ++$i) {
		assertType('non-empty-list<mixed>', $arr);
	}
	assertType('list<mixed>', $arr);
}

/** @param array<string, int> $arr */
function fooStringKeyed(array $arr): void {
	assertType('array<string, int>', $arr);
	foreach ($arr as $v) {
		assertType('array<string, int>', $arr);
	}
	assertType('array<string, int>', $arr);
}
