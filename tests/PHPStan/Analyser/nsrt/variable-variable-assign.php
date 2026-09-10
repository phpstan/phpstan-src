<?php

namespace VariableVariableAssign;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

function knownName(): void
{
	$name = 'value';
	$$name = 1;
	assertType('1', $value);
	assertNativeType('1', $value);
}

function possibleNames(bool $condition): void
{
	$first = [];
	$second = [];
	$name = $condition ? 'first' : 'second';
	$$name = ['value'];
	assertType("array{}|array{'value'}", $first);
	assertType("array{}|array{'value'}", $second);
	assertNativeType("array{}|array{'value'}", $first);
}

function unknownName(string $name): void
{
	$value = [];
	$$name = ['value'];
	assertType("array{}|array{'value'}", $value);
	assertNativeType("array{}|array{'value'}", $value);
}

function updateKnownName(): void
{
	$name = 'counter';
	$counter = 1;
	$$name += 2;
	assertType('3', $counter);
	assertNativeType('3', $counter);
}

/** @param 'value' $name */
function documentedName(string $name): void
{
	$value = 1;
	$$name = 2;
	assertType('2', $value);
	assertNativeType('1|2', $value);
}
