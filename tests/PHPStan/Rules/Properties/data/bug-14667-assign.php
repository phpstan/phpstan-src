<?php // lint >= 8.0

namespace Bug14667Assign;

/** @param mixed $row */
function testImplicitMixed($row): void
{
	if (property_exists($row, 'prop')) {
		$row->prop = 'value';
	}
}

function testExplicitMixed(mixed $row): void
{
	if (property_exists($row, 'prop')) {
		$row->prop = 'value';
	}
}

/** @param object|string $row */
function testObjectOrString($row): void
{
	if (property_exists($row, 'prop')) {
		$row->prop = 'value';
	}
}
