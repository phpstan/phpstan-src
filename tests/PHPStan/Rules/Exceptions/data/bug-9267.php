<?php declare(strict_types = 1);

namespace Bug9267;

class FooException extends \Exception {}

function bar(\ReflectionMethod $r): void {
	try {
		$r->invokeArgs(new C, array());
	}
	catch (FooException $e) {
		print "CAUGHT FOO!\n";
	}
}

class C {
	/** @return never */
	public function test() {
		throw new FooException("");
	}
}
