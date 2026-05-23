<?php // lint >= 8.0

namespace Bug14667StaticMethods;

/** @param mixed $row */
function testStaticImplicitMixed($row): void
{
	if (method_exists($row, 'foo')) {
		$row::foo();
	}
}

function testStaticExplicitMixed(mixed $row): void
{
	if (method_exists($row, 'foo')) {
		$row::foo();
	}
}

/** @param object|string $row */
function testStaticObjectOrString($row): void
{
	if (method_exists($row, 'foo')) {
		$row::foo();
	}
}

function testStaticObject(object $row): void
{
	if (method_exists($row, 'foo')) {
		$row::foo();
	}
}

/** @param class-string|object $row */
function testStaticClassStringOrObject($row): void
{
	if (method_exists($row, 'foo')) {
		$row::foo();
	}
}

/** @param class-string $row */
function testStaticClassString(string $row): void
{
	if (method_exists($row, 'foo')) {
		$row::foo();
	}
}
