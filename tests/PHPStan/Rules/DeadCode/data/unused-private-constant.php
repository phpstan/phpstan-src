<?php

namespace UnusedPrivateConstant;

class Foo
{

	private const FOO_CONST = 1;

	private const BAR_CONST = 2;

	public function doFoo()
	{
		echo self::FOO_CONST;
	}

}

class TestExtension
{
	private const USED = 1;

	private const UNUSED = 2;
}
