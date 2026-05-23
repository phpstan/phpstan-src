<?php // lint >= 8.0

namespace Bug14667Nsrt;

use function PHPStan\Testing\assertType;

/** @param mixed $row */
function testMixed($row): void
{
	if (property_exists($row, 'prop')) {
		assertType('class-string|(object&hasProperty(prop))', $row);
	}
}

function testExplicitMixed(mixed $row): void
{
	if (property_exists($row, 'prop')) {
		assertType('class-string|(object&hasProperty(prop))', $row);
	}
}

/** @param mixed $row */
function testMethodExistsMixed($row): void
{
	if (method_exists($row, 'foo')) {
		assertType('(class-string&hasMethod(foo))|(object&hasMethod(foo))', $row);
		$row->foo();
	}
}

function testMethodExistsExplicitMixed(mixed $row): void
{
	if (method_exists($row, 'foo')) {
		assertType('(class-string&hasMethod(foo))|(object&hasMethod(foo))', $row);
		$row->foo();
	}
}

/** @param object|string $row */
function testMethodExistsObjectOrString($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

function testMethodExistsObject(object $row): void
{
	if (method_exists($row, 'bar')) {
		$row->bar();
	}
}

/** @param mixed $x */
function testMethodExistsMixedChained($x): void
{
	if (method_exists($x, 'getName') && $x->getName() !== null) {
		echo $x->getName();
	}
}

/** @param class-string|object $row */
function testMethodExistsClassStringOrObject($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

/** @param class-string $row */
function testMethodExistsClassString(string $row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo(); // error: Cannot call method foo() on class-string.
	}
}
