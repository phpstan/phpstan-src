<?php // lint >= 8.3

namespace PromoteMissingOverride;

class Foo
{

	public function doFoo(): void
	{

	}

}

class Bar extends Foo
{

	public function doFoo(): void
	{

	}


	public function doBar(): void
	{

	}

	#[\Override]
	public function doBaz(): void
	{
	}

}
