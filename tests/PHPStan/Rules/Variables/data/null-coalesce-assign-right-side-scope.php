<?php declare(strict_types = 1);

namespace NullCoalesceAssignRightSideScope;

final class Foo
{

	public string $nonNullable = '';

	public static string $staticNonNullable = '';

	/** @var array{foo?: string, bar?: string} */
	public array $data = [];

}

function property(Foo $foo): void
{
	$foo->nonNullable ??= $foo->nonNullable ?? null;
}

function staticProperty(): void
{
	Foo::$staticNonNullable ??= Foo::$staticNonNullable ?? null;
}

function propertyOffset(Foo $foo): void
{
	$foo->data['foo'] ??= $foo->data['bar'] ?? null;
}

/** @param array{a?: array{b?: string, c?: string}} $data */
function nestedOffset(array $data): void
{
	$data['a']['b'] ??= $data['a']['c'] ?? null;
}

/** @param array<string, string> $data */
function nonConstantArray(array $data, string $key): void
{
	$data[$key] ??= $data['fallback'] ?? null;
}

function undefinedVariable(): void
{
	$undefined ??= $undefined ?? 1;
}
