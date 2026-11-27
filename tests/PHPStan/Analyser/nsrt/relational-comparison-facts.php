<?php // lint >= 8.0

declare(strict_types = 1);

namespace RelationalComparisonFacts;

use function PHPStan\Testing\assertType;

/** @param list<int> $l */
function countFirst(array $l, int $i): void
{
	if ($i >= 0 && count($l) > $i) {
		assertType('non-empty-list<int>', $l);
	}
	if (count($l) > $i && $i >= 0) {
		assertType('non-empty-list<int>', $l);
	}
}

/** @param list<int> $l */
function countNested(array $l, int $i): void
{
	if (count($l) > $i) {
		if ($i >= 0) {
			assertType('non-empty-list<int>', $l);
		}
	}
}

function eqAfterRange(int $a, int $b): void
{
	if ($b > 0) {
		if ($a === $b) {
			assertType('int<1, max>', $a);
		}
	}
}

function rangeAfterEq(int $a, int $b): void
{
	if ($a === $b) {
		if ($b > 0) {
			assertType('int<1, max>', $a);
		}
	}
}

function ltThenRange(int $a, int $b): void
{
	if ($a < $b) {
		if ($a >= 0) {
			assertType('int<1, max>', $b);
		}
	}
}

function rangeThenLt(int $a, int $b): void
{
	if ($a >= 0) {
		if ($a < $b) {
			assertType('int<1, max>', $b);
		}
	}
}

function reassigned(int $a, int $b): void
{
	if ($a === $b) {
		$a = -5;
		if ($b > 0) {
			assertType('-5', $a);
		}
	}
}

function notIdenticalTrue(int $a, int $b): void
{
	if ($a !== $b) {
		if ($b > 0) {
			assertType('int', $a);
		}
	}
}

function smallerFalse(int $a, int $b): void
{
	if (!($a < $b)) {
		if ($b >= 10) {
			assertType('int<10, max>', $a);
		}
	}
}

function floats(float $a, float $b): void
{
	if ($a < $b) {
		if ($a >= 0.0) {
			assertType('float', $b);
		}
	}
}

function chain(int $a, int $b, int $c): void
{
	if ($a < $b && $b < $c) {
		if ($a >= 0) {
			assertType('int<2, max>', $c);
		}
	}
}

function merged(int $a, int $b, bool $x): void
{
	if ($x) {
		if ($a !== $b) {
			return;
		}
	}
	if ($b > 0) {
		assertType('int', $a);
	}
}

/** @param list<int> $l */
function countNotGreater(array $l, int $i): void
{
	if (count($l) <= $i) {
		if ($i >= 1) {
			assertType('list<int>', $l);
		}
	}
}

/** @param array<string, int> $m */
function countRecursive(array $m, int $i): void
{
	if (count($m) > $i) {
		if ($i >= -1) {
			assertType('array<string, int>', $m);
		}
	}
}

function mixedIdentity(mixed $a, ?string $b): void
{
	if ($a === $b) {
		if ($b !== null) {
			assertType('string', $a);
		}
	}
}

class PropertyOperand
{

	public int $limit = 80;

	public function check(int $length, bool $comment): void
	{
		if ($length <= $this->limit) {
			return;
		}
		if ($comment) {
			assertType('true', $length > $this->limit);
		}
	}

}

function loopGuard(string $format): void
{
	$length = strlen($format);
	for ($i = 0; $i < $length; $i++) {
		if ($format[$i] === '\\') {
			assertType('true', $i < $length);
		}
	}
}

function earlyReturnThenRange(?int $left, ?int $right): void
{
	if ($left === null || $right === null) {
		return;
	}
	if ($left >= $right) {
		return;
	}
	if ($left >= 900) {
		assertType('int<901, max>', $right);
	}
}

function repeatedInOr(int $diff, int $skip): void
{
	if ($diff < $skip || assertType('false', $diff < $skip)) {
	}
}

/**
 * @param array<mixed> $first
 * @param array<mixed> $second
 */
function arrayIdentity(array $first, array $second): void
{
	if ($first === $second) {
		assertType('true', $first === $second);
	}
}
