<?php // lint >= 8.1

namespace RestrictedUsageNullable;

class Foo
{

	public const FOO = 1;

	public $foo;

	public function doFoo(): void
	{

	}

}

class FooStatic
{

	public static $foo;

	public static function doFoo(): void
	{

	}

}

class Bar
{

	public $foo;

	public function doFoo(): void
	{

	}

}

class Test
{

	public function doTest(?Foo $foo, ?FooStatic $fooStatic, Foo|Bar|null $fooOrBar, Foo|false $fooOrFalse): void
	{
		$foo->doFoo();
		$foo?->doFoo();
		$foo->doFoo(...);
		$foo->foo;
		$foo?->foo;
		$foo::FOO;
		$fooStatic::doFoo();
		$fooStatic::doFoo(...);
		$fooStatic::$foo;
		$fooOrBar->doFoo();
		$fooOrBar->foo;
		$fooOrFalse->doFoo();
		$fooOrFalse->foo;
	}

}
