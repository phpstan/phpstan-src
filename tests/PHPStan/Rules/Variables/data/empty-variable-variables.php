<?php declare(strict_types = 1);

namespace EmptyVariableVariables;

function unknownName(string $name): void
{
	var_dump(empty(${$name}));
}

function neverDefined(): void
{
	$name = 'undefinedVariable';
	var_dump(empty(${$name}));
}

function alwaysDefinedAndTruthy(): void
{
	$nonFalsy = 'foo';
	$name = 'nonFalsy';
	var_dump(empty(${$name}));
}

function maybeDefined(bool $b): void
{
	if ($b) {
		$maybe = 'foo';
	}
	$name = 'maybe';
	var_dump(empty(${$name}));
}
