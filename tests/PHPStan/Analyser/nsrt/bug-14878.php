<?php declare(strict_types = 1);

namespace Bug14878;

use function PHPStan\Testing\assertType;

function test($a, $b): void
{
	if (
		in_array($a, [1, 2])
		&& $b === true)
	{

	} elseif (
		$a == 3) {
		assertType('mixed', $b);
	}
}

function testStrictElseIf($a, $b): void
{
	if (
		in_array($a, [1, 2])
		&& $b === true)
	{

	} elseif (
		$a === 3) {
		assertType('mixed', $b);
	}
}

function testPlainElse($a, $b): void
{
	if (
		in_array($a, [1, 2])
		&& $b === true)
	{

	} else {
		assertType('mixed', $b);
	}
}

// Same degenerate-condition pattern without in_array: a loose `==` whose
// falsey narrowing of $a is a no-op on a `mixed` type.
function testLooseEqual($a, $b): void
{
	if (
		($a == 1 || $a == 2)
		&& $b === true)
	{

	} elseif ($a == 3) {
		assertType('mixed', $b);
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
