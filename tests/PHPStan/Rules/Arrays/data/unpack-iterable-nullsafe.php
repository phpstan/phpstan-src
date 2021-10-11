<?php // lint >= 8.0

namespace UnpackIterableNullsafe;

class Bar
{
	/** @var int[] */
	public array $array;
}

class Foo
{

	public function doFoo(?Bar $bar)
	{
		$foo = [
			...$bar?->array,
		];
	}

}
