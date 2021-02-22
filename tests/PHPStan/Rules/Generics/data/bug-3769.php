<?php

namespace Bug3769;

use function PHPStan\Analyser\assertType;

/**
 * @template K of array-key
 * @param array<K, int> $in
 * @return array<K, string>
 */
function stringValues(array $in): array {
	assertType('array<K of (int|string) (function Bug3769\stringValues(), argument), int>', $in);
	return array_map(function (int $int): string {
		return (string) $int;
	}, $in);
}

/**
 * @param array<int, int> $foo
 * @param array<string, int> $bar
 * @param array<int> $baz
 */
function foo(
	array $foo,
	array $bar,
	array $baz
): void {
	assertType('array<int, string>', stringValues($foo));
	assertType('array<string, string>', stringValues($bar));
	assertType('array<string>', stringValues($baz));
};

/**
 * @template T of \stdClass|\Exception
 * @param T $foo
 */
function fooUnion($foo): void {
	assertType('T of Exception|stdClass (function Bug3769\fooUnion(), argument)', $foo);
}
