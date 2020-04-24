<?php

namespace CallStaticMethods;

class Foo
{
	/**
	 * @param mixed $foo
	 */
	public function a($foo) {
		$foo::test();
		$foo::test(1, 2, 3);
	}

	public function b($foo) {
		$foo::test();
		$foo::test(1, 2, 3);
	}
}
