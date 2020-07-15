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
