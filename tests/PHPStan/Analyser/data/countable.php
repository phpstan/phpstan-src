<?php

namespace CountablePhpDocInheritance;

use function PHPStan\Testing\assertType;

class Foo implements \Countable {
	public function count() : int {
		return 0;
	}

	static public function doFoo() {
		$foo = new Foo();
		assertType('int<0, max>', $foo->count());
	}
}

