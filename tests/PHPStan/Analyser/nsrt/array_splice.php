<?php

namespace ArraySplice;

use function PHPStan\Testing\assertType;

final class Foo
{
	/** @var bool */
	public $abc = false;

	/** @var string */
	public $def = 'def';
}

/**
 * @param array<int, int> $arr
 * @return void
 */
function insertViaArraySplice(array $arr): void
{
	$brr = $arr;
	array_splice($brr, 0, 0, 1);
	assertType('array<int, int>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, [1]);
	assertType('array<int, int>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, '');
	assertType('array<int, \'\'|int>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, ['']);
	assertType('array<int, \'\'|int>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, null);
	assertType('array<int, int>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, [null]);
	assertType('array<int, int|null>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, new Foo());
	assertType('array<int, bool|int|string>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, [new \stdClass()]);
	assertType('array<int, int|stdClass>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, false);
	assertType('array<int, int|false>', $brr);

	$brr = $arr;
	array_splice($brr, 0, 0, [false]);
	assertType('array<int, int|false>', $brr);
}
