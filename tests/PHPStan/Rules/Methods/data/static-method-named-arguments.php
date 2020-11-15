<?php

namespace StaticMethodNamedArguments;

class Foo
{

	public static function doFoo(int $i, int $j): void
	{

	}

	public function doBar(): void
	{
		self::doFoo(i: 1);
	}

}
