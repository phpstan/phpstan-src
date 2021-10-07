<?php // lint >= 8.0

namespace InvalidEncapsedPartNullsafe;

class Bar
{
	public \stdClass $obj;
}

function doFoo(?Bar $bar) {
	"{$bar?->obj} bar";
}
