<?php // lint >= 8.0

declare(strict_types = 1);

namespace PrintNullsafe;

class Bar
{
	/** @var int[] */
	public array $array;
}

function def(?Bar $bar)
{
	print $bar?->array;
}
