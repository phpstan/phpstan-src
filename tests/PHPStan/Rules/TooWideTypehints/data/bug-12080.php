<?php

namespace Bug12080;

class C {
	/**
	 * @param mixed $x
	 * @param-out string|int $x
	 */
	public function foo(&$x): void {
		$x = "foo";
	}
}

class D extends C {
	public function foo(&$x): void {
		$x = 42;
	}
}
