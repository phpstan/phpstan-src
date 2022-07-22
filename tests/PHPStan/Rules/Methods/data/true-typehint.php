<?php // lint >= 8.2

namespace NativeTrueType;

class Truthy {
	public true $truthy = true;

	public function foo(true $v): true {

	}

	function trueUnion(true|null $trueUnion): void
	{
	}

	function trueUnionReturn(): true|null
	{
	}
}
