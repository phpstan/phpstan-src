<?php

namespace Countable;

use function PHPStan\Testing\assertType;

class Foo implements \Countable {
	public function count() : int {
		return 0;
	}
}

$foo = new Foo();
assertType('int<0, max>', $foo->count());
