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

trait FooPrivate
{

	abstract private function doBar(string $i): int;

}

class Baz
{
	use FooPrivate;

	private function doBar(int $i): int
	{

	}
}

class Lorem
{
	use Foo;

	protected function doBar(string $i): int
	{

	}
}

class Ipsum
{
	use Foo;

	public static function doBar(string $i): int
	{

	}
}

trait FooStatic
{
	abstract public static function doBar(string $i): int;
}

class Dolor
{
	use FooStatic;

	public function doBar(string $i): int
	{

	}
}
