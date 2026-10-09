<?php // lint >= 8.6

declare(strict_types = 1);

namespace Clamp;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 100> $percent
 * @param int<-50, 50> $delta
 * @param list<int> $list
 */
function doFoo(int $i, float $f, int|float $n, int $a, int $b, string $s, ?int $maybe, int $percent, int $delta, array $list): void
{
	assertType('int<1, 10>', clamp($i, 1, 10));
	assertType('float', clamp($f, 1.0, 10.0));
	assertType('1.0|10.0|int<1, 10>', clamp($i, 1.0, 10.0));
	assertType('float|int<0, 1>', clamp($n, 0, 1));
	assertType('int', clamp($i, $a, $b));
	assertType('int<0, 100>', clamp($percent + $delta, 0, 100));
	assertType('5', clamp(5, 1, 10));
	assertType('10', clamp(15, 1, 10));
	assertType('1', clamp(-3, 1, 10));
	assertType('string', clamp($s, 'a', 'm'));
	assertType('int<1, 10>', clamp($maybe, 1, 10));
	assertType('int<1, 10>', clamp(max: 10, value: $i, min: 1));
	assertType('mixed', clamp($list, [0], [10]));
}
