<?php

namespace RequiredAfterOptional;

class Foo
{

	public function doFoo($foo = null, $bar): void // not OK
	{

	}

	public function doBar(int $foo = null, $bar): void // is OK
	{
	}

	public function doBaz(int $foo = 1, $bar): void // not OK
	{
	}

	public function doLorem(bool $foo = true, $bar): void // not OK
	{
	}

	public function doDolor(?int $foo = 1, $bar): void // not OK
	{
	}

	public function doSit(?int $foo = null, $bar): void // not OK
	{
	}
}
