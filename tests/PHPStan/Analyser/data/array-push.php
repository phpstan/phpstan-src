<?php

namespace ArrayPush;

use stdClass;

use function array_push;
use function PHPStan\Testing\assertType;

/**
 * @param string[] $a
 * @param int[] $b
 * @param non-empty-array<int> $c
 * @param array<int|string> $d
 */
function arrayPush(array $a, array $b, array $c, array $d): void
{
	array_push($a, ...$b);
	assertType('non-empty-array<int|string>', $a);

	array_push($b, ...[]);
	assertType('array<int>', $b);

	array_push($c, ...[19, 'baz', false]);
	assertType('non-empty-array<\'baz\'|int|false>', $c);

	/** @var array<bool|null> $d1 */
	$d1 = [];
	array_push($d, ...$d1);
	assertType('non-empty-array<bool|int|string|null>', $d);
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

	$f = [17];
	/** @var array<bool|null> $f1 */
	$f1 = [];
	array_push($f, ...$f1);
	assertType('non-empty-array<int, bool|int|null>', $f);

	$g = [new stdClass()];
	array_push($g, ...[new stdClass(), new stdClass()]);
	assertType('array{stdClass, stdClass, stdClass}', $g);
}
