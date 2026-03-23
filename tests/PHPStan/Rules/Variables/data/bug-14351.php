<?php declare(strict_types = 1);

namespace Bug14351;

class C {
	function foo(): void {
		try {
			throw new \Exception();
		} catch (\Exception $this) { // should report: Cannot re-assign $this
		}
	}
}

function foo(): void {
	global $this; // should report: Cannot use $this as global variable
}

function bar(): void {
	static $this; // should report: Cannot use $this as static variable
}
