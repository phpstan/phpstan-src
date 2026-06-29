<?php declare(strict_types = 1);

namespace Bug14878;

use function PHPStan\Testing\assertType;

// The `$b === true` and `$b === true && $cond` statements inside the branches
// are what regressed: with the bug $b was narrowed to `mixed~true`, so they
// emitted "Strict comparison ... will always evaluate to false" and "Result of
// && is always false". They are referenced from BooleanAndConstantConditionRuleTest
// and StrictComparisonOfDifferentTypesRuleTest as a regression guard.

function test($a, $b, $cond): void
{
	if (
		in_array($a, [1, 2])
		&& $b === true)
	{

	} elseif (
		$a == 3) {
		assertType('mixed', $b);

		$b === true;
		$result = $b === true && $cond;
	}
}

function testStrictElseIf($a, $b, $cond): void
{
	if (
		in_array($a, [1, 2])
		&& $b === true)
	{

	} elseif (
		$a === 3) {
		assertType('mixed', $b);

		$b === true;
		$result = $b === true && $cond;
	}
}

function testPlainElse($a, $b, $cond): void
{
	if (
		in_array($a, [1, 2])
		&& $b === true)
	{

	} else {
		assertType('mixed', $b);

		$b === true;
		$result = $b === true && $cond;
	}
}

// Same degenerate-condition pattern without in_array: a loose `==` whose
// falsey narrowing of $a is a no-op on a `mixed` type.
function testLooseEqual($a, $b, $cond): void
{
	if (
		($a == 1 || $a == 2)
		&& $b === true)
	{

	} elseif ($a == 3) {
		assertType('mixed', $b);

		$b === true;
		$result = $b === true && $cond;
	}
}

// A genuinely conditional holder must still fire: $a === 1 makes the strict
// in_array() antecedent true, so $b === true must have been false.
function testMeaningfulHolderStillFires($a, $b): void
{
	if (
		in_array($a, [1, 2], true)
		&& $b === true)
	{

	} elseif ($a === 1) {
		assertType('mixed~true', $b);
	}
}
