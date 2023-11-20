<?php // lint >= 8.3

namespace UnusedPrivateConstantDynamicFetch;

class Foo
{

	private const FOO = 1;

	public function doFoo(string $s): void
	{
		echo self::{$s};
	}

}

class Bar
{

	private const FOO = 1;

	public function doFoo(self $a, string $s): void
	{
		echo $a::{$s};
	}

}

class Baz
{

	private const FOO = 1;

	public function doFoo(\stdClass $a, string $s): void
	{
		echo $a::{$s};
	}

}
