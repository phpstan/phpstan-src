<?php // lint >= 8.6

declare(strict_types = 1);

namespace Clamp;

use DateTime;
use DateTimeImmutable;
use function PHPStan\Testing\assertType;

/** @phpstan-impure */
function impureInt(): int
{
	return rand(0, 100) - 50;
}

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
	assertType('int<1, 10>', clamp(impureInt(), 1, 10));
}

/**
 * @param 1|2|3 $lowUnion
 * @param 8|9|10 $highUnion
 * @param int<0, 5> $lowRange
 * @param int<8, 10> $highRange
 */
function doBounds(int $i, int $lowUnion, int $highUnion, int $lowRange, int $highRange): void
{
	assertType('int<1, 10>', clamp($i, $lowUnion, $highUnion));
	assertType('5', clamp(5, $lowUnion, $highUnion));
	assertType('1|2|3', clamp(0, $lowUnion, $highUnion));
	assertType('int<0, 10>', clamp($i, $lowRange, $highRange));
	assertType('int<8, 10>', clamp(12, $lowRange, $highRange));
	assertType('int<0, 5>', clamp(-1, $lowRange, $highRange));
}

/**
 * @param int|array<int> $intOrArray
 * @param list<int> $list
 */
function doArrays(int|array $intOrArray, array $list): void
{
	assertType('array<int>|int<1, 10>', clamp($intOrArray, 1, 10));
	assertType('list<int>', clamp($list, [0], [10]));
	assertType('array{10}|array{1}|array{5}', clamp([5], [1], [10]));
	assertType('1|10|array{5}', clamp([5], 1, 10));
}

function doDates(DateTimeImmutable $date, DateTimeImmutable $min, DateTimeImmutable $max, DateTime $mutable): void
{
	assertType('DateTimeImmutable', clamp($date, $min, $max));
	assertType('DateTime|DateTimeImmutable', clamp($mutable, $min, $max));
	assertType('DateTimeImmutable', clamp(new DateTimeImmutable('2025-08-01'), new DateTimeImmutable('2025-08-15'), new DateTimeImmutable('2025-08-31')));
}

/**
 * @param array{int, 1, 10} $tuple
 * @param array{1, 10} $bounds
 * @param list<int> $list
 */
function doSplat(int $i, array $tuple, array $bounds, array $list): void
{
	assertType('mixed', clamp(...$tuple));
	assertType('mixed', clamp($i, ...$bounds));
	assertType('mixed', clamp(...$list));
	assertType('mixed', clamp(...[$i], ...[1], ...[10]));
	assertType('mixed', clamp($i, 1, ...[10]));
}
