<?php // lint >= 8.2

namespace NativeTrueType;

class Truthy {
	public true $truthy = true;

	public function foo(true $v): true {

	}
}

function alwaysTrue(): true
{
	return true;
}
