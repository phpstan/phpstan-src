<?php // lint >= 8.1

declare(strict_types = 1);

namespace UnsetVariableVariables;

class Foo
{

	public function __construct(
		public readonly int $readOnly,
		public int $regular,
	)
	{
	}

}

function unknownName(string $name): void
{
	unset(${$name});
}

function neverDefined(): void
{
	$name = 'undefinedVariable';
	unset(${$name});
}

function alwaysDefined(): void
{
	$defined = 1;
	$name = 'defined';
	unset(${$name});
}

function dynamicPropertyName(Foo $foo): void
{
	$name = 'readOnly';
	unset($foo->{$name});

	$regularName = 'regular';
	unset($foo->{$regularName});
}
