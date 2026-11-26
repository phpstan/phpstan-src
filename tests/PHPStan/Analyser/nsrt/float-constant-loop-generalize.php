<?php declare(strict_types = 1);

namespace FloatConstantLoopGeneralize;

use function PHPStan\Testing\assertType;

/** @param non-negative-float $x */
function takesNonNegativeFloat(float $x): void
{
}

/** @param non-negative-int $x */
function takesNonNegativeInt(int $x): void
{
}

/** @param list<string> $items */
function constantStep(array $items): void
{
	$total = 0.0;
	foreach ($items as $item) {
		$total += 1.5;
	}
	assertType('0.0|1.5|float<3.0, max>', $total);
	takesNonNegativeFloat($total);

	$count = 0;
	foreach ($items as $item) {
		$count += 2;
	}
	assertType('0|2|int<4, max>', $count);
	takesNonNegativeInt($count);
}

function counter(int $limit): void
{
	for ($x = 0.0; $x < $limit; $x += 0.25) {
		assertType('0.0|float<0.25, max>', $x);
	}

	for ($i = 0; $i < $limit; $i += 1) {
		assertType('int<0, max>', $i);
	}
}

/** @param list<string> $items */
function decreasing(array $items): void
{
	$budget = 10.0;
	foreach ($items as $item) {
		$budget -= 0.5;
	}
	assertType('9.5|10.0|float<min, 9.0>', $budget);
}

/** @param list<string> $items */
function halving(array $items): void
{
	$size = 1.0;
	foreach ($items as $item) {
		$size /= 2;
	}
	assertType('0.5|1.0|float<min, 0.25>', $size);
}

/** @param list<string> $items */
function withNan(array $items, bool $b): void
{
	$value = $b ? NAN : 0.0;
	foreach ($items as $item) {
		$value += 1.0;
	}
	assertType('0.0|1.0|NAN|float<2.0, max>', $value);
}

/** @param list<string> $items */
function onlyNan(array $items): void
{
	$value = NAN;
	foreach ($items as $item) {
		$value = 2.0;
	}
	assertType('2.0|NAN', $value);
}

/** @param list<string> $items */
function toggled(array $items): void
{
	$sign = 1.0;
	foreach ($items as $item) {
		$sign = -$sign;
	}
	assertType('-1.0|1.0', $sign);
}
