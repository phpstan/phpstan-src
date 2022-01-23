<?php // lint >= 8.1

namespace FirstClassStaticMethodCallable;

class Foo
{

	public static function doFoo(int $i): void
	{
		self::doFoo(...);
	}

}
