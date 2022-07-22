<?php // lint >= 8.2

namespace NativeUnionTypes82;

class Truthy {
	public true $truthy = true;

	public function foo(true $v): true {

	}
}
