<?php declare(strict_types = 1);

namespace Bug9267;

class FooException extends \Exception {}

class C {
	/** @return never */
	public function test(): never {
		throw new FooException("");
	}
}

function bar(\ReflectionMethod $r): void {
	try {
		$r->invokeArgs(new C, array());
	}
	catch (FooException $e) {
		print "CAUGHT FOO!\n";
	}
}

function baz(\ReflectionMethod $r): void {
	try {
		$r->invoke(new C);
	}
	catch (FooException $e) {
		print "CAUGHT FOO!\n";
	}
}
