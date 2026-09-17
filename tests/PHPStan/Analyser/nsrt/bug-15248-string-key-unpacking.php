<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15248StringKeyUnpacking;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, int> $rest
 * @return array<int|string, int>
 */
function foo(string $key, array $rest): array
{
	$a = [9223372036854775807 => 1, $key => 2, ...$rest];

	assertType('non-empty-array<9223372036854775807|string, int>', $a);

	return $a;
}

/**
 * @param array<string, int> $rest
 */
function unpackedItemAfterMaxIntKey(array $rest): void
{
	$a = [9223372036854775807 => 1, ...$rest];
	assertType('non-empty-array<9223372036854775807|string, int>', $a);
}

/**
 * @param array<string, int> $rest
 */
function byRefItemAfterUnpackedStringKeyedArray(array $rest, int $x): void
{
	$a = [...$rest, &$x];
	assertType('non-empty-array<0|string, int>', $a);
}

function byRefItemAfterUnpackedStringKeyedConstantArray(int $x): void
{
	$constant = ['a' => 1, 'b' => 2];
	$a = [...$constant, &$x];
	$x = 9;
	assertType('array{a: 1, b: 2, 0: 9}', $a);
}
