<?php // lint >= 8.1

namespace StaticMethodCallablePhpVersions;

class Foo
{

	public static function doFoo(): void
	{
	}

	public function doBar(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			$f = self::doFoo(...);
		}

		if (PHP_VERSION_ID < 80100) {
			$g = self::doFoo(...);
		}

		$h = self::doFoo(...);
	}

}
