<?php // lint >= 8.0

namespace Bug14667Static;

/** @param mixed $row */
function testStaticImplicitMixed($row): void
{
	if (property_exists($row, 'prop')) {
		echo $row::$prop;
	}
}

function testStaticExplicitMixed(mixed $row): void
{
	if (property_exists($row, 'prop')) {
		echo $row::$prop;
	}
}

/** @param object|string $row */
function testStaticObjectOrString($row): void
{
	if (property_exists($row, 'prop')) {
		echo $row::$prop;
	}
}

function testStaticObject(object $row): void
{
	if (property_exists($row, 'prop')) {
		echo $row::$prop;
	}
}

/** @param class-string|object $row */
function testStaticClassStringOrObject($row): void
{
	if (property_exists($row, 'prop')) {
		echo $row::$prop;
	}
}

/** @param class-string $row */
function testStaticClassString(string $row): void
{
	if (property_exists($row, 'prop')) {
		echo $row::$prop;
	}
}
