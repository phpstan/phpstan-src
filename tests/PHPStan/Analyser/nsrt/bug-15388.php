<?php declare(strict_types = 1);

namespace Bug15388;

use function PHPStan\Testing\assertType;

/** @param list<int> $vals */
function foo(array $vals): void {
	assertType('(float|int)', array_reduce($vals, function($carry, $val) {
		return $carry + map($val);
	}, 0));
	assertType('(float|int)', array_reduce($vals, function($carry, $val) {
		return $carry + $val;
	}, 0));
	assertType('(float|int)', array_reduce($vals, function($carry, int $val) {
		return $carry - $val;
	}, 0));
	assertType('(float|int)', array_reduce($vals, function($carry, int $val) {
		return $carry * $val;
	}, 1));
}

function map(int $val): int {
	return $val;
}

/**
 * @template T
 * @param T $t
 * @param mixed $m
 */
function templateOfMixed($t, $m, int $i): void
{
	assertType('(float|int)', $m + $i);
	assertType('(float|int)', $t + $i);
	assertType('(float|int)', $i + $t);
	assertType('(float|int)', $t + 1);
	assertType('(float|int)', $t - $i);
	assertType('(float|int)', $t - 1);
	assertType('(float|int)', $t * $i);
	assertType('(float|int)', $t / $i);
	assertType('(float|int)', $t ** $i);
	assertType('(float|int)', $t << $i);
	assertType('int', $t % $i);

	$a = $t;
	$a += $i;
	assertType('(float|int)', $a);

	$a = $t;
	$a++;
	assertType('(float|int)', $a);
}
