<?php // lint >= 8.0

namespace InvalidBinaryNullsafe;

class Bar
{
	public array $array;
}

function dooFoo(?Bar $bar)
{
	$bar?->array + '2';
}
