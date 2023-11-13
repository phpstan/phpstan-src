<?php // lint >= 8.0

namespace OverridingTraitMethods;

trait Foo
{

	public function doFoo(string $i): int
	{

	}

	abstract public function doBar(string $i): int;

}

class Bar
{

	use Foo;

	public function doFoo(int $i): string
	{
		// ok, trait method not abstract
	}

	public function doBar(int $i): int
	{
		// error
	}

}
