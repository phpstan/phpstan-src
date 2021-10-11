<?php // lint >= 8.0

namespace InvalidComparisonNullsafe;

class Bar
{
	public \stdClass $val;
}

function doFoo(?Bar $bar, int $a)
{
	$bar?->val == $a;
}
