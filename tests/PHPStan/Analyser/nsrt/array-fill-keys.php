<?php

namespace ArrayFillKeys;

use function PHPStan\Testing\assertType;

class Foo
{
	/** @phpstan-return 'foo' */
	public function __toString(): string
	{
		return 'foo';
	}
}

class Bar
{
	public function __toString(): string
	{
		return 'bar';
	}
}

class Baz {}

function withBoolKey() : array
{
	assertType("array{1: 'b'}", array_fill_keys([true], 'b'));
	assertType("array{: 'b'}", array_fill_keys([false], 'b'));
}

function withFloatKey() : array
{
	assertType("array{1.5: 'b'}", array_fill_keys([1.5], 'b'));
}

function withIntegerKey() : array
{
	assertType("array{99: 'b'}", array_fill_keys([99], 'b'));
}

function withNumericStringKey() : array
{
	assertType("array{999: 'b'}", array_fill_keys(["999"], 'b'));
}

function withObjectKey() : array
{
	assertType("array{foo: 'b'}", array_fill_keys([new Foo()], 'b'));
	assertType("non-empty-array<string, 'b'>", array_fill_keys([new Bar()], 'b'));
	assertType("*ERROR*", array_fill_keys([new Baz()], 'b'));
}

function withUnionKeys(): void
{
	$arr1 = ['foo', rand(0, 1) ? 'bar1' : 'bar2', 'baz'];
	assertType("non-empty-array<'bar1'|'bar2'|'baz'|'foo', 'b'>", array_fill_keys($arr1, 'b'));

	$arr2 = ['foo'];
	if (rand(0, 1)) {
		$arr2[] = 'bar';
	}
	$arr2[] = 'baz';
	assertType("non-empty-array<'bar'|'baz'|'foo', 'b'>", array_fill_keys($arr2, 'b'));
}

function withOptionalKeys(): void
{
	$arr1 = ['foo', 'bar'];
	if (rand(0, 1)) {
		$arr1[] = 'baz';
	}
	assertType("array{foo: 'b', bar: 'b', baz?: 'b'}", array_fill_keys($arr1, 'b'));

	/** @var array{0?: 'foo', 1: 'bar', }|array{0: 'baz', 1?: 'foobar'} $arr2 */
	$arr2 = [];
	assertType("array{baz: 'b', foobar?: 'b'}|array{foo?: 'b', bar: 'b'}", array_fill_keys($arr2, 'b'));
}

/**
 * @param Bar[] $foo
 * @param int[] $bar
 * @param Foo[] $baz
 * @param float[] $floats
 * @param array<int, int|string|bool> $mixed
 * @param list<string> $list
 * @param Baz[] $objectsWithoutToString
 */
function withNotConstantArray(array $foo, array $bar, array $baz, array $floats, array $mixed, array $list, array $objectsWithoutToString): void
{
	assertType("array<string, null>", array_fill_keys($foo, null));
	assertType("array<int, null>", array_fill_keys($bar, null));
	assertType("array<'foo', null>", array_fill_keys($baz, null));
	assertType("array<numeric-string, null>", array_fill_keys($floats, null));
	assertType("array<bool|int|string, null>", array_fill_keys($mixed, null));
	assertType('array<string, null>', array_fill_keys($list, null));
	assertType('*ERROR*', array_fill_keys($objectsWithoutToString, null));

	if (array_key_exists(17, $mixed)) {
		assertType('non-empty-array<bool|int|string, null>', array_fill_keys($mixed, null));
	}

	if (array_key_exists(17, $mixed) && $mixed[17] === 'foo') {
		assertType('non-empty-array<bool|int|string, null>', array_fill_keys($mixed, null));
	}
}
