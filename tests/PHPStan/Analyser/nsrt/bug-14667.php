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
	}
}

function testMethodExistsExplicitMixed(mixed $row): void
{
	if (method_exists($row, 'foo')) {
		assertType('(class-string&hasMethod(foo))|(object&hasMethod(foo))', $row);
	}
}
