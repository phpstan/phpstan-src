<?php declare(strict_types = 1);

namespace Bug15091;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function reassignFirstListItem(): void
{
	$values = ['foo', 'bar'];
	[$values, $second] = $values;
	assertType("'bar'", $second);
	assertNativeType("'bar'", $second);
}

function reassignFirstKeyedItem(): void
{
	$values = ['first' => 1, 'second' => 'value'];
	['first' => $values, 'second' => $second] = $values;
	assertType("'value'", $second);
}

function reassignNestedItem(): void
{
	$values = [['inner', 42], 'last'];
	[[$values, $number], $last] = $values;
	assertType('42', $number);
	assertType("'last'", $last);
}

/** @param array{string, callable(): int} $value */
function callableSecond(array $value): void
{
	[$value, $callable] = $value;
	assertType('callable(): int', $callable);
	assertNativeType('mixed', $callable);
	assertType('string', $value);
	assertNativeType('mixed', $value);
}

function sourceInLaterTarget(): void
{
	$values = ['first', 'second'];
	[$first, $values] = $values;
	assertType("'first'", $first);
	assertType("'second'", $values);
}

function skipSlotAfterReassigningSource(): void
{
	$values = ['first', 'skipped', 'third'];
	[$values, , $third] = $values;
	assertType("'third'", $third);
}

function withoutReassigningSource(): void
{
	$values = ['first', 'second'];
	[$first, $second] = $values;
	assertType("'first'", $first);
	assertNativeType("'first'", $first);
	assertType("'second'", $second);
}
