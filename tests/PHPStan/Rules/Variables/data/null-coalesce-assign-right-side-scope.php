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

/** @param \ArrayAccess<string, string> $a */
function nonNullableOffsetAccess(\ArrayAccess $a): void
{
	$a['foo'] ??= $a['foo'] ?? null;
}

/** @param \ArrayAccess<string, string> $a */
function nonNullableOffsetAccessDifferentOffset(\ArrayAccess $a): void
{
	$a['foo'] ??= $a['bar'] ?? null;
}

/** @param \ArrayAccess<string, string|null> $a */
function nullableOffsetAccess(\ArrayAccess $a): void
{
	$a['foo'] ??= $a['foo'] ?? null;
}

/** @param \ArrayObject<string, string> $data */
function arrayObjectOffset(\ArrayObject $data): void
{
	$data['foo'] ??= $data['foo'] ?? null;
}
