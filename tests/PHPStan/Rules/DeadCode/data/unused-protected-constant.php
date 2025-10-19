<?php // lint >= 8.1

namespace UnusedProtectedConstant;

class Foo
{

	protected const FOO_CONST = 1;

	protected const BAR_CONST = 2;

	final protected const BAZ_CONST = 2;

	public function doFoo()
	{
		echo self::FOO_CONST;
	}

}

final class Bar
{

	protected const FOO_CONST = 1;

	protected const BAR_CONST = 2;

	final protected const BAZ_CONST = 2;

	public function doFoo()
	{
		echo self::FOO_CONST;
	}

}
