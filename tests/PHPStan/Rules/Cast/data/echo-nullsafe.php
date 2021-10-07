<?php // lint >= 8.0

declare(strict_types = 1);

namespace EchoNullsafe;

class Bar
{
	/** @var int[] */
	public array $array;
}

function def(?Bar $bar)
{
	echo $bar?->array;
}
