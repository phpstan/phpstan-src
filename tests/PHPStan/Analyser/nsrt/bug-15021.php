<?php declare(strict_types = 1);

namespace Bug15021;

use function PHPStan\Testing\assertType;

/** @param array{foo?: string, bar?: string} $data */
function optionalOffset(array $data): void
{
	$data['foo'] ??= assertType('array{bar?: string}', $data);
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
	$foo->data['foo'] ??= assertType('array{bar?: string}', $foo->data);
}

/** @param \ArrayAccess<string, string> $a */
function nonNullableOffsetAccess(\ArrayAccess $a): void
{
	$a['foo'] ??= assertType('string|null', $a['foo']);
}

/** @param \ArrayAccess<string, string> $a */
function nonNullableOffsetAccessCoalesce(\ArrayAccess $a): void
{
	$x = $a['foo'] ?? assertType('string|null', $a['foo']);
}

/** @param \ArrayAccess<string, string> $a */
function nonNullableOffsetAccessDifferentOffset(\ArrayAccess $a): void
{
	$a['foo'] ??= assertType('string|null', $a['bar']);
}

/** @param \ArrayAccess<string, string|null> $a */
function nullableOffsetAccess(\ArrayAccess $a): void
{
	$a['foo'] ??= assertType('string|null', $a['foo']);
}

/** @param \ArrayAccess<string, string|null> $a */
function nullableOffsetAccessCoalesce(\ArrayAccess $a): void
{
	$x = $a['foo'] ?? assertType('string|null', $a['foo']);
}

/** @param \ArrayObject<string, string> $data */
function arrayObjectOffset(\ArrayObject $data): void
{
	$data['foo'] ??= assertType('string|null', $data['foo']);
}

/** @param \ArrayObject<string, string> $data */
function arrayObjectOffsetCoalesce(\ArrayObject $data): void
{
	$x = $data['foo'] ?? assertType('string|null', $data['foo']);
}

/** @param array{foo?: string, bar?: string} $data */
function issetOnRightSide(array $data): void
{
	$data['foo'] ??= isset($data['bar']) ? assertType('string', $data['bar']) : 'fallback';
}

/** @param array{foo?: string, bar?: string} $data */
function emptyOnRightSide(array $data): void
{
	$data['foo'] ??= empty($data['bar']) ? 'fallback' : assertType('non-falsy-string', $data['bar']);
}

/** @param array{foo?: string, bar?: string} $data */
function unsetTargetBeforeAssignOp(array $data): void
{
	unset($data['foo']);
	$data['foo'] ??= assertType('array{bar?: string}', $data);
}

/** @param array{foo?: string, bar?: string} $data */
function emptyTargetBeforeAssignOp(array $data): void
{
	if (empty($data['foo'])) {
		$data['foo'] ??= assertType('array{bar?: string}', $data);
	}
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpInsideEmpty(array $data): void
{
	if (empty($data['foo'] ??= assertType('array{bar?: string}', $data))) {
		echo 'empty';
	}
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpInsideIsset(array $data): void
{
	if (isset($data['foo'] ??= assertType('array{bar?: string}', $data))) {
		echo 'isset';
	}
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpInsideUnsetOffset(array $data): void
{
	$other = ['x' => 1, 'fallback' => 2];
	unset($other[$data['foo'] ??= assertType('array{bar?: string}', $data)]);
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpResultInsideEmpty(array $data): void
{
	if (empty($data['foo'] ??= $data['bar'] ?? 'fallback')) {
		assertType("array{foo: ''|'0', bar?: string}", $data);
	}
}

/** @param array{foo?: string, bar?: string} $data */
function assignOpResultInsideIsset(array $data): void
{
	if (isset($data['foo'] ??= $data['bar'] ?? null)) {
		assertType('array{foo: string, bar?: string}', $data);
	}
}

/** @param array{foo?: string, bar?: string} $data */
function unsetAssignOpResultOffset(array $data): void
{
	$other = ['x' => 1, 'fallback' => 2];
	unset($other[$data['foo'] ??= $data['bar'] ?? 'fallback']);
	assertType('array{x?: 1, fallback?: 2}', $other);
}
