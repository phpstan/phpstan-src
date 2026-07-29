<?php declare(strict_types = 1);

namespace Bug15021;

use function PHPStan\Testing\assertType;

/** @param array{foo?: string, bar?: string} $data */
function optionalOffset(array $data): void
{
	$data['foo'] ??= assertType('array{foo?: string, bar?: string}', $data);
}

/** @param array{foo?: string, bar?: string} $data */
function optionalOffsetResult(array $data): void
{
	$data['foo'] ??= $data['bar'] ?? null;
	assertType('array{foo: string|null, bar?: string}', $data);
}

/** @param array<string, string> $data */
function nonConstantArray(array $data, string $key): void
{
	$data[$key] ??= assertType('array<string, string>', $data);
}

/** @param array<string, string> $data */
function nonConstantArrayConstantKey(array $data): void
{
	$data['foo'] ??= assertType('array<string, string>', $data);
}

/** @param array<string, string|null> $data */
function nonConstantArrayNullableValue(array $data): void
{
	$data['foo'] ??= assertType('string|null', $data['foo']);
}

/** @param array{a?: array{b?: string, c?: string}} $data */
function nestedOptionalOffset(array $data): void
{
	$data['a']['b'] ??= assertType('array{a?: array{b?: string, c?: string}}', $data);
}

class Foo
{

	public string $nonNullable = '';

	public static string $staticNonNullable = '';

	/** @var array{foo?: string, bar?: string} */
	public array $data = [];

}

function nonNullableProperty(Foo $foo): void
{
	$foo->nonNullable ??= assertType('string', $foo->nonNullable);
}

function nonNullableStaticProperty(): void
{
	Foo::$staticNonNullable ??= assertType('string', Foo::$staticNonNullable);
}

function propertyOffset(Foo $foo): void
{
	$foo->data['foo'] ??= assertType('array{foo?: string, bar?: string}', $foo->data);
}
