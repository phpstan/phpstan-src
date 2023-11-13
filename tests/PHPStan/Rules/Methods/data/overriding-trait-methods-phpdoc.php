<?php // lint >= 8.0

namespace OverridingTraitMethodsPhpDoc;

trait Foo
{

	public function doFoo(int $i): int
	{

	}

	abstract public function doBar(string $i): int;

}

class Bar
{

	use Foo;

	/**
	 * @param positive-int $i
	 */
	public function doFoo(int $i): string
	{
		// ok, trait method not abstract
	}

	/**
	 * @param non-empty-string $i
	 */
	public function doBar(string $i): int
	{
		// error
	}

}
