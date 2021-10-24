<?php // lint >= 8.0

namespace NonexistentOffsetNullsafe;

class Foo
{

	/** @var array{a: int} */
	public array $array = [
		'a' => 1,
	];

}

function nonexistentOffsetOnArray(?Foo $foo): void
{
	echo $foo?->array['a'];
	echo $foo?->array[1];
}
