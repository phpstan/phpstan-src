<?php declare(strict_types = 1);

namespace ShiftLeftUnboundedRange;

use RuntimeException;
use function PHPStan\Testing\assertType;

/**
 * @param non-negative-int $nonNegative
 * @param positive-int $positive
 * @param int<min, 0> $nonPositive
 * @param negative-int $negative
 * @param int<min, 10> $unboundedMin
 * @param int<-10, max> $unboundedMax
 */
function shiftRanges(int $nonNegative, int $positive, int $nonPositive, int $negative, int $unboundedMin, int $unboundedMax): void
{
	assertType('int', $nonNegative << 1);
	assertType('int', $positive << 1);
	assertType('int', $nonPositive << 1);
	assertType('int', $negative << 1);
	assertType('int', $unboundedMin << 1);
	assertType('int', $unboundedMax << 1);

	assertType('int<0, max>', $nonNegative << 0);
	assertType('int<1, max>', $positive << 0);
	assertType('int<min, 0>', $nonPositive << 0);
	assertType('int<min, -1>', $negative << 0);
	assertType('int<min, 10>', $unboundedMin << 0);
	assertType('int<-10, max>', $unboundedMax << 0);

	assertType('int<0, max>', $nonNegative >> 1);
	assertType('int<0, max>', $positive >> 1);
	assertType('int<min, 0>', $nonPositive >> 1);
	assertType('int<min, -1>', $negative >> 1);
	assertType('int<min, 5>', $unboundedMin >> 1);
	assertType('int<-5, max>', $unboundedMax >> 1);
}

function readU64(string $bytes): int
{
	$value = 0;
	for ($i = 0; $i < 8; $i++) {
		$value = ($value << 8) | ord($bytes[$i]);
	}

	assertType('int', $value);
	assertType('bool', $value < 0);
	if ($value < 0) {
		throw new RuntimeException('Value is too large for 64-bit signed integer');
	}

	assertType('int<0, max>', $value);
	return $value;
}
