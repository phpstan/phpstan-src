<?php // lint >= 8.1

namespace UnusedPrivateConstantEnum;

enum Foo
{

	private const TEST = 1;
	private const TEST_2 = 1;

	public function doFoo(): void
	{
		echo self::TEST;
	}

}
