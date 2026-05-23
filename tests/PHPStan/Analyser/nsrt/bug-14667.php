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
