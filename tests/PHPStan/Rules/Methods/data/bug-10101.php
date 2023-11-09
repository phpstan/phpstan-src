<?php

namespace Bug10101;

abstract class A extends \ArrayIterator {
	function next(): void {}
}

class B extends A {
	#[\ReturnTypeWillChange]
	public function next()
	{
	}
}
