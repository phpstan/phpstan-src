<?php declare(strict_types = 1);

namespace Bug14871;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function logicalOr(bool $cond, bool $f): void
{
	if ($cond || $f) {
		$x = 1;
	}

	if ($cond || $f) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertType('1', $x);
	}
}

function logicalAnd(bool $cond, bool $f): void
{
	if ($cond && $f) {
		$z = 1;
	}

	if ($cond && $f) {
		assertVariableCertainty(TrinaryLogic::createYes(), $z);
		assertType('1', $z);
	}
}

function wordOperators(bool $cond, bool $f): void
{
	if ($cond or $f) {
		$x = 1;
	}

	if ($cond or $f) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
}

function nestedCompound(bool $a, bool $b, bool $c): void
{
	if (($a && $b) || $c) {
		$x = 1;
	}

	if (($a && $b) || $c) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
}

function comparisonOperands(int $a, int $b): void
{
	if ($a > 0 || $b > 0) {
		$x = 1;
	}

	if ($a > 0 || $b > 0) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
}

function typeNarrowingCarried(?int $a, bool $b): void
{
	if ($a !== null || $b) {
		$x = 'set';
	}

	if ($a !== null || $b) {
		assertType("'set'", $x);
	}
}

// A condition with a side effect in one operand must still narrow correctly
// after the `if` (the whole-condition pinning must not re-run the assignment).
function assignmentInOperand(string $foo): int
{
	if (!ctype_digit($foo) || ($foo = intval($foo)) < 1) {
		return -1;
	}

	assertType('int<1, max>', $foo);

	return $foo;
}
