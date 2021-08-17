<?php

namespace AccessPrivateConstantThroughStatic;

class Foo
{

	private const FOO = 1;

	public function doFoo()
	{
		static::FOO;
		static::BAR; // reported by a different rule
	}

}

final class Bar
{

	private const FOO = 1;

	public function doFoo()
	{
		static::FOO;
		static::BAR; // reported by a different rule
	}

}
