<?php declare(strict_types = 1);

namespace VariableVariablesIsset;

use function PHPStan\Testing\assertType;

function unknownName(string $name): void
{
	assertType('bool', isset(${$name}));
	assertType('mixed', ${$name} ?? 'fallback');
}

function neverDefined(): void
{
	$name = 'undefinedVariable';
	assertType('false', isset(${$name}));
	assertType("'fallback'", ${$name} ?? 'fallback');
}

function alwaysDefined(): void
{
	$defined = 5;
	$name = 'defined';
	assertType('true', isset(${$name}));
	assertType('5', ${$name} ?? 'fallback');
}

function alwaysDefinedNullable(?int $nullable): void
{
	$name = 'nullable';
	assertType('bool', isset(${$name}));
	assertType("'fallback'|int", ${$name} ?? 'fallback');
}

function maybeDefined(bool $b): void
{
	if ($b) {
		$maybe = 5;
	}
	$name = 'maybe';
	assertType('bool', isset(${$name}));
	assertType("5|'fallback'", ${$name} ?? 'fallback');
}

function multipleNames(bool $b): void
{
	$firstDefined = 1;
	$secondDefined = 2;
	$name = $b ? 'firstDefined' : 'secondDefined';
	assertType('true', isset(${$name}));
	assertType('1|2', ${$name} ?? 'fallback');

	$undefinedName = $b ? 'firstUndefined' : 'secondUndefined';
	assertType('false', isset(${$undefinedName}));
	assertType("'fallback'", ${$undefinedName} ?? 'fallback');
}
