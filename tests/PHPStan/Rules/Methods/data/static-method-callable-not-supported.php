<?php // lint >= 8.1

namespace StaticMethodCallableNotSupported;

class Foo
{

	public static function doFoo(): void
	{
		self::doFoo(...);
	}

}
