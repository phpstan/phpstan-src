<?php

namespace ReturnNullsafeByRef;

class Foo
{

	public function doFoo(?\stdClass $foo)
	{
		function &() use ($foo) {
			if (rand(0, 1)) {
				return $foo;
			}

			return $foo?->bar->foo;
		};
	}

	public function &doBar()
	{
		if (rand(0, 1)) {
			return $foo;
		}

		return $foo?->bar->foo;
	}

}

function &foo(): void
{
	if (rand(0, 1)) {
		return $foo;
	}

	return $foo?->bar->foo;
}
