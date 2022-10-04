<?php // lint >= 8.1

namespace Bug8101;

class A {
	public function __construct(public readonly int $myProp) {}
}

class B extends A
{
	// This should be reported as an error, as a readonly prop cannot be redeclared.
	public function __construct(public readonly int $myProp) {
		parent::__construct($myProp);
	}
}

class C extends A
{
	public function __construct(public readonly int $anotherProp) {
		parent::__construct($anotherProp);
	}
}

class ANonReadonly {
	public function __construct(public int $myProp) {}
}

class BNonReadonly extends ANonReadonly
{
	public function __construct(public int $myProp) {
		parent::__construct($myProp);
	}
}
