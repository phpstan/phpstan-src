<?php declare(strict_types = 1);

namespace IssetVariableVariables;

class Foo
{

	public int $notNullable = 1;

	public ?int $nullable = null;

	public static int $staticNotNullable = 1;

	public static ?int $staticNullable = null;

}

function unknownName(string $name): void
{
	var_dump(isset(${$name}));
}

function neverDefined(): void
{
	$name = 'undefinedVariable';
	var_dump(isset(${$name}));
}

function alwaysDefined(): void
{
	$notNullable = 1;
	$name = 'notNullable';
	var_dump(isset(${$name}));
}

function maybeDefined(bool $b): void
{
	if ($b) {
		$maybe = 1;
	}
	$name = 'maybe';
	var_dump(isset(${$name}));
}

function dynamicPropertyName(Foo $foo): void
{
	$name = 'notNullable';
	var_dump(isset($foo->{$name}));

	$nullableName = 'nullable';
	var_dump(isset($foo->{$nullableName}));
}

function dynamicStaticPropertyName(): void
{
	$name = 'staticNotNullable';
	var_dump(isset(Foo::${$name}));

	$nullableName = 'staticNullable';
	var_dump(isset(Foo::${$nullableName}));
}
