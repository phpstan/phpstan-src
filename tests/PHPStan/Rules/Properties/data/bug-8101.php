<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug8101;

class A {
	public function __construct(public readonly int $myProp) {}
}

class B extends A {
	// This should be reported as an error, as a readonly prop cannot be redeclared.
	public function __construct(public readonly int $myProp) {
		parent::__construct($myProp);
	}
}

$foo = new B(7);
