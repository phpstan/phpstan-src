<?php declare(strict_types = 1);

namespace NullCoalesceVariableVariables;

class Foo
{

	public int $notNullable = 1;

	public ?int $nullable = null;

	public static int $staticNotNullable = 1;

	public static ?int $staticNullable = null;

}

function unknownName(string $name): void
{
	echo ${$name} ?? null;
	echo ${$name} ?? 'foo';
}

function neverDefined(): void
{
	$name = 'undefinedVariable';
	echo ${$name} ?? null;
}

function neverDefinedMultipleNames(bool $b): void
{
	$name = $b ? 'undefinedVariable' : 'anotherUndefinedVariable';
	echo ${$name} ?? null;
}

function alwaysDefined(): void
{
	$notNullable = 1;
	$name = 'notNullable';
	echo ${$name} ?? null;
}

function alwaysDefinedMultipleNames(bool $b): void
{
	$notNullable = 1;
	$alsoNotNullable = 2;
	$name = $b ? 'notNullable' : 'alsoNotNullable';
	echo ${$name} ?? null;
}

function maybeDefined(bool $b): void
{
	if ($b) {
		$maybe = 1;
	}
	$name = 'maybe';
	echo ${$name} ?? null;
}

function definedAndNullable(?int $nullable): void
{
	$name = 'nullable';
	echo ${$name} ?? null;
}

function dynamicPropertyName(Foo $foo): void
{
	$name = 'notNullable';
	echo $foo->{$name} ?? null;

	$nullableName = 'nullable';
	echo $foo->{$nullableName} ?? null;
}

function dynamicStaticPropertyName(): void
{
	$name = 'staticNotNullable';
	echo Foo::${$name} ?? null;

	$nullableName = 'staticNullable';
	echo Foo::${$nullableName} ?? null;
}

function offsetOnVariableVariable(): void
{
	$name = 'undefinedArray';
	echo ${$name}['foo'] ?? null;
}
