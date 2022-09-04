<?php // lint >= 8.2

namespace NativeTrueType;

use function PHPStan\Testing\assertType;

class Truthy {
	public true $truthy = true;

	public function foo(true $v): true {
		assertType('true', $v);
	}

	function trueUnion(true|null $trueUnion): void
	{
	}

	function trueUnionReturn(): true|null
	{
	}
}

function foo(Truthy $truthy) {
	assertType('true', $truthy->foo(true));
}
