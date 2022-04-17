<?php

namespace ArrayUnshift;

use function array_unshift;
use function PHPStan\Testing\assertType;

/**
 * @param string[] $a
 * @param int[] $b
 * @param non-empty-array<int> $c
 */
function arrayUnshift(array $a, array $b, array $c): void
{
	array_unshift($a, ...$b);
	assertType('non-empty-array<int|string>', $a);

	array_unshift($b, ...[]);
	assertType('array<int>', $b);

	array_unshift($c, ...[19, 'baz', false]);
	assertType('non-empty-array<\'baz\'|int|false>', $c);
}

function arrayUnshiftConstantArray(): void
{
	$a = ['foo' => 17, 'a', 'bar' => 18,];
	array_unshift($a, ...[19, 'baz', false]);
	assertType('array{0: 19, 1: \'baz\', 2: false, foo: 17, 3: \'a\', bar: 18}', $a);

	$b = ['foo' => 17, 'a', 'bar' => 18];
	array_unshift($b, 19, 'baz', false);
	assertType('array{0: 19, 1: \'baz\', 2: false, foo: 17, 3: \'a\', bar: 18}', $b);

	$c = ['foo' => 17, 'a', 'bar' => 18];
	array_unshift($c, ...[]);
	assertType('array{foo: 17, 0: \'a\', bar: 18}', $c);

	$d = [];
	array_unshift($d, ...[]);
	assertType('array{}', $d);

	$e = [];
	array_unshift($e, 19, 'baz', false);
	assertType('array{19, \'baz\', false}', $e);
}
