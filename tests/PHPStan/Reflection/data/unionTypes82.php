<?php // lint >= 8.2

namespace NativeUnionTypes82;

class Foo {
	function trueUnion(true|null $trueUnion): void
	{
	}

	function trueUnion(): true|null
	{
	}

	function alwaysTrue(): true
	{
		return true;
	}
}
