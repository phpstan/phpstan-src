<?php

namespace ArrayPush;

use function array_push;
use function PHPStan\Testing\assertType;

/**
 * @param string[] $a
 * @param int[] $b
 * @param non-empty-array<int> $c
 */
function arrayPush(array $a, array $b, array $c): void
{
	array_push($a, ...$b);
	assertType('non-empty-array<int|string>', $a);

	array_push($b, ...[]);
	assertType('array<int>', $b);

	array_push($c, ...[19, 'baz', false]);
	assertType('non-empty-array<\'baz\'|int|false>', $c);
}

function arrayPushConstantArray(): void
{
	$a = ['foo' => 17, 'a', 'bar' => 18,];
	array_push($a, ...[19, 'baz', false]);
	assertType('array{foo: 17, 0: \'a\', bar: 18, 1: 19, 2: \'baz\', 3: false}', $a);

	$b = ['foo' => 17, 'a', 'bar' => 18];
	array_push($b, 19, 'baz', false);
	assertType('array{foo: 17, 0: \'a\', bar: 18, 1: 19, 2: \'baz\', 3: false}', $b);

	$c = ['foo' => 17, 'a', 'bar' => 18];
	array_push($c, ...[]);
	assertType('array{foo: 17, 0: \'a\', bar: 18}', $c);

	$d = [];
	array_push($d, ...[]);
	assertType('array{}', $d);

	$e = [];
	array_push($e, 19, 'baz', false);
	assertType('array{19, \'baz\', false}', $e);
}
