<?php // lint >= 8.1

namespace FirstClassCallableStaticMethodWithoutSideEffect;

class Foo
{

	public static function doFoo(): void
	{
		$f = self::doFoo(...);

		self::doFoo(...);
	}

}

class Bar
{

	static function doFoo(): never
	{
		throw new \Exception();
	}

	/**
	 * @throws \Exception
	 */
	static function doBar()
	{
		throw new \Exception();
	}

	function doBaz(): void
	{
		$f = self::doFoo(...);
		self::doFoo(...);

		$g = self::doBar(...);
		self::doBar(...);
	}

}
