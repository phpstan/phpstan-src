<?php declare(strict_types = 1);

namespace Bug14654;

use function PHPStan\Testing\assertType;

function foo(): void {
	$a = \ord('a');
	$b = \ord('b');

	assertType('int<0, 255>', $a);
	assertType('int<0, 255>', $b);
	assertType('int<0, 255>', $a ^ $b);
	assertType('int<0, 255>', $a | $b);
	assertType('int<0, 255>', $a & $b);
}

/**
 * @param int<0, 255> $c
 */
function withConstantOperand(int $c): void {
	assertType('int<0, 255>', $c ^ 42);
	assertType('int<0, 255>', 42 ^ $c);
	assertType('int<0, 255>', $c | 42);
	assertType('int<0, 42>', $c & 42);
}

/**
 * @param int<0, 20> $x
 * @param int<0, 20> $y
 */
function smallRanges(int $x, int $y): void {
	assertType('int<0, 31>', $x ^ $y);
	assertType('int<0, 31>', $x | $y);
	assertType('int<0, 20>', $x & $y);
}

/**
 * @param int<0, 255> $a
 * @param int<0, 20> $x
 */
function differentRangeSizes(int $a, int $x): void {
	assertType('int<0, 20>', $a & $x);
	assertType('int<0, 20>', $x & $a);
}

/**
 * @param int<0, max> $unbounded
 * @param int<0, 255> $a
 */
function unboundedRanges(int $unbounded, int $a): void {
	assertType('int', $unbounded ^ $a);
	assertType('int', $unbounded | $a);
}

/**
 * @param int<-10, 10> $signed
 * @param int<0, 20> $x
 */
function negativeRanges(int $signed, int $x): void {
	assertType('int', $signed ^ $x);
	assertType('int', $signed | $x);
}

/**
 * @param int<0, 255> $a
 * @param int<0, 20> $x
 * @param int<min, 10> $minBounded
 * @param int<-5, max> $maxBounded
 */
function bitwiseNot(int $a, int $x, int $minBounded, int $maxBounded): void {
	assertType('int<-256, -1>', ~$a);
	assertType('int<-21, -1>', ~$x);
	assertType('int<-11, max>', ~$minBounded);
	assertType('int<min, 4>', ~$maxBounded);
}
