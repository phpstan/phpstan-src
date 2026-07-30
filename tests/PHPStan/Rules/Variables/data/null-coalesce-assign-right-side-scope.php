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

/** @param array{foo?: string, bar?: string} $data */
function issetOnRightSide(array $data): void
{
	$data['foo'] ??= isset($data['bar']) ? $data['bar'] : 'fallback';
}

/** @param array{foo?: string, bar?: string} $data */
function emptyOnRightSide(array $data): void
{
	$data['foo'] ??= empty($data['bar']) ? 'fallback' : $data['bar'];
}

/** @param array{foo?: string, bar?: string} $data */
function unsetTargetBeforeAssignOp(array $data): void
{
	unset($data['foo']);
	$data['foo'] ??= $data['bar'] ?? null;
}

/** @param array{foo?: string, bar?: string} $data */
function emptyTargetBeforeAssignOp(array $data): void
{
	if (empty($data['foo'])) {
		$data['foo'] ??= $data['bar'] ?? null;
	}
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpInsideEmpty(array $data): void
{
	if (empty($data['foo'] ??= $data['bar'] ?? null)) {
		echo 'empty';
	}
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpInsideUnsetOffset(array $data): void
{
	$other = ['x' => 1, 'fallback' => 2];
	unset($other[$data['foo'] ??= $data['bar'] ?? 'fallback']);
}
