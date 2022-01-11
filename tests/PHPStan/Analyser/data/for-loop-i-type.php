<?php

namespace ForLoopIType;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doBar() {
		for($i = 1; $i < 50; $i++) {
			assertType('int<1, 49>', $i);
		}
		for($i = 50; $i > 0; $i--) {
			assertType('int<1, max>', $i); // could be int<1, 50>
		}
	}

}
