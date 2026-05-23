<?php // lint >= 8.0

namespace Bug14667Methods;

/** @param mixed $row */
function testImplicitMixed($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

function testExplicitMixed(mixed $row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

/** @param object|string $row */
function testObjectOrString($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

function testObject(object $row): void
{
	if (method_exists($row, 'bar')) {
		$row->bar();
	}
}

/** @param mixed $x */
function testMixedChained($x): void
{
	if (method_exists($x, 'getName') && $x->getName() !== null) {
		echo $x->getName();
	}
}

/** @param class-string|object $row */
function testClassStringOrObject($row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo();
	}
}

/** @param class-string $row */
function testClassString(string $row): void
{
	if (method_exists($row, 'foo')) {
		$row->foo(); // error: Cannot call method foo() on class-string.
	}
}
