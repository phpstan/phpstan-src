<?php // lint >= 8.0
declare(strict_types = 1);

namespace ImplodeNullsafeFunction;

class Bar
{
	/** @var array<int,array<string>> */
	public array $array;
}

class Foo
{

	public function doFoo(?Bar $bar)
	{
		implode($bar?->array);
	}

}
