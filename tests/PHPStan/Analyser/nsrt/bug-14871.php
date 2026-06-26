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

// https://phpstan.org/r/aab74e73-2bfe-432b-8bcd-f9b939d2eaab
// An `if`/`elseif`/`else` chain repeated with identical compound conditions must
// carry definedness across every branch, not just the first.
function compoundElseIfChain(bool $rel, bool $document, bool $overwrite): void
{
	if ($rel || $overwrite) {
		$vvv = 1;
	} elseif ($document) {
		$aaa = 2;
	} else {
		$eee = 3;
	}

	if ($rel || $overwrite) {
		assertVariableCertainty(TrinaryLogic::createYes(), $vvv);
	} elseif ($document) {
		assertVariableCertainty(TrinaryLogic::createYes(), $aaa);
	} else {
		assertVariableCertainty(TrinaryLogic::createYes(), $eee);
	}
}

// The same chain with single-variable conditions (a pre-existing limitation for
// the non-first branches) must work too.
function singleVarElseIfChain(bool $a, bool $b): void
{
	if ($a) {
		$x = 1;
	} elseif ($b) {
		$y = 2;
	} else {
		$z = 3;
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	} elseif ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $y);
	} else {
		assertVariableCertainty(TrinaryLogic::createYes(), $z);
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
