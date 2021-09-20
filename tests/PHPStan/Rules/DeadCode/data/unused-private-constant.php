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


final class P
{

	private const JSON_OBJECT_START = 17;
	private const JSON_OBJECT_END   = 18;

	public function ignoreObjectBlock(): void
	{
		do {
			$code = doFoo();

			// recursively ignore nested objects
			if ($code !== self::JSON_OBJECT_START) {
				continue;
			}

			$this->ignoreObjectBlock();
		} while ($code !== self::JSON_OBJECT_END);
	}
}
