<?php

namespace ForLoopIType;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doBar() {
		for($i = 1; $i < 50; $i++) {
			assertType('int<1, 49>', $i);
		}

		assertType('50', $i);

		for($i = 50; $i > 0; $i--) {
			assertType('int<1, max>', $i); // could be int<1, 50>
		}

		assertType('0', $i);
	}

	public function doBaz() {
		for($i = 1; $i < 50; $i += 2) {
			assertType('int<1, 49>', $i);
		}

		assertType('int<50, 51>', $i);
	}

	public function doLOrem() {
		for($i = 1; $i < 50; $i++) {
			break;
		}

		assertType('int<1, 50>', $i);
	}

}
