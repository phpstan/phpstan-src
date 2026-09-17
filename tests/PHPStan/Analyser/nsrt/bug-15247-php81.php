<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15247Php81;

use function PHPStan\Testing\assertType;

function stringKeysSpread(int $x): void
{
	$list = ['a' => 10, 'b' => 20];
	$a = [...$list, &$x];
	$a[0] = 'foo';

	// string keys are preserved by unpacking, so they don't take up an implicit index
	assertType("'foo'", $x);
	assertType("array{a: 10, b: 20, 0: 'foo'}", $a);
}

function mixedKeysSpread(int $x): void
{
	$list = ['a' => 10, 5, 'b' => 20, 6];
	$a = [...$list, &$x];
	$a[2] = 'foo';
	assertType("'foo'", $x);
	assertType("array{a: 10, 0: 5, b: 20, 1: 6, 2: 'foo'}", $a);
}

/** @param array<string, int> $map */
function unknownStringKeysSpread(array $map, int $x): void
{
	$a = [...$map, &$x];
	$a[0] = 'foo';

	// string keys are preserved, so the reference is at offset 0
	assertType("'foo'", $x);
	assertType("non-empty-array<0|string, 'foo'|int>&hasOffsetValue(0, 'foo')", $a);
}
