<?php // lint >= 8.0

namespace InvalidCastNullsafe;

class Bar
{
	public \stdClass $obj;
}

function doFoo(
	?Bar $bar
) {
	(string) $bar?->obj;
};
