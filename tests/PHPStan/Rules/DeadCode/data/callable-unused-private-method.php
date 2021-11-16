<?php // lint >= 8.1

namespace CallableUnusedPrivateMethod;

class Foo
{

	public function doFoo(): void
	{
		$f = $this->doBar(...);
	}

	private function doBar(): void
	{

	}

}

class Bar
{

	public function doFoo(): void
	{
		$f = self::doBar(...);
	}

	private static function doBar(): void
	{

	}

}
