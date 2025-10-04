<?php // lint >= 8.0

namespace ArrayCombinePHP8;

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

function withBoolKey(): void
{
	$a = [true, 'red', 'yellow'];
	$b = ['avocado', 'apple', 'banana'];

	assertType("array{1: 'avocado', red: 'apple', yellow: 'banana'}", array_combine($a, $b));

	$c = [false, 'red', 'yellow'];
	$d = ['avocado', 'apple', 'banana'];

	assertType("array{'': 'avocado', red: 'apple', yellow: 'banana'}", array_combine($c, $d));
}

function withFloatKey(): void
{
	$a = [1.5, 'red', 'yellow'];
	$b = ['avocado', 'apple', 'banana'];

	assertType("array{'1.5': 'avocado', red: 'apple', yellow: 'banana'}", array_combine($a, $b));
}

function withIntegerKey(): void
{
	$a = [1, 2, 3];
	$b = ['avocado', 'apple', 'banana'];

	assertType("array{1: 'avocado', 2: 'apple', 3: 'banana'}", array_combine($a, $b));
}

function withNumericStringKey(): void
{
	$a = ["1", "2", "3"];
	$b = ['avocado', 'apple', 'banana'];

	assertType("array{1: 'avocado', 2: 'apple', 3: 'banana'}", array_combine($a, $b));
}

function withObjectKey() : void
{
	$a = [new Foo, 'red', 'yellow'];
	$b = ['avocado', 'apple', 'banana'];

	assertType("array{foo: 'avocado', red: 'apple', yellow: 'banana'}", array_combine($a, $b));
	assertType("non-empty-array<string, 'apple'|'avocado'|'banana'>", array_combine([new Bar, 'red', 'yellow'], $b));
	assertType("*NEVER*", array_combine([new Baz, 'red', 'yellow'], $b));
}

function withUnionConstArrays(): void
{
	if (rand(0, 1)) {
		$a = [1];
		$b = ['avocado'];
	} else {
		$a = ["2", "3"];
		$b = ['apple', 'banana'];
	}

	assertType("array{1: 'avocado'}|array{2: 'apple', 3: 'banana'}", array_combine($a, $b));
}

function withUnionConstArraysDifferentKeyValueCount(): void
{
	if (rand(0, 1)) {
		$a = [1];
		$b = [];
	} else {
		$a = ["2", "3"];
		$b = ['apple', 'banana'];
	}

	assertType("*NEVER*", array_combine($a, $b));
}

function withUnionConstArraysDifferentArraysCount(): void
{
	if (rand(0, 1)) {
		$a = [1];
		$b = ['avocado'];
	} else {
		$a = ["2", "3"];
		if (rand(0, 1)) {
			$b = ['apple', 'banana'];
		} else {
			$b = ['banana', 'pear'];
		}
	}

	assertType("non-empty-array<1|'2'|'3', 'apple'|'avocado'|'banana'|'pear'>", array_combine($a, $b));
}

/**
 * @param non-empty-array<int, 'foo'|'bar'|'baz'> $a
 * @param non-empty-array<int, 'apple'|'avocado'|'banana'> $b
 */
function withNonEmptyArray(array $a, array $b): void
{
	assertType("non-empty-array<'bar'|'baz'|'foo', 'apple'|'avocado'|'banana'>", array_combine($a, $b));
}

function withDifferentNumberOfElements(): void
{
	assertType('*NEVER*', array_combine(['foo'], ['bar', 'baz']));
}

function bug11819(): void
{
	$keys = [1, 2, 3];
	$types = array_combine($keys, array_fill(0, \count($keys), false));
	$types[] = 'foo';
	assertType('array{1: false, 2: false, 3: false, 4: \'foo\'}', $types);
}
