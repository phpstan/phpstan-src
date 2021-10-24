<?php // lint >= 8.0

namespace IterablesInForeachNullsafe;

class Foo
{

	/** @var int[] */
	public array $array;
}

function doFoo(?Foo $foo)
{
	foreach ($foo?->array as $x) {
		// pass
	}
}
