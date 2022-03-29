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
	assertType("*NEVER*", array_fill_keys([new Baz()], 'b'));
}
