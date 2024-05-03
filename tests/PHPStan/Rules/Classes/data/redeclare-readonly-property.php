<?php declare(strict_types = 1); // lint >= 8.1

namespace RedeclareReadonlyProperty;

class A {
	protected readonly string $nonPromotedProp;

	public function __construct(public readonly int $myProp) {
		$this->nonPromotedProp = 'aaa';
	}
}

class B1 extends A {
	// This should be reported as an error, as a readonly prop cannot be redeclared.
	public function __construct(public readonly int $myProp) {
		parent::__construct($myProp);
	}
}

class B2 extends A {
	// different property
	public function __construct(public readonly int $foo) {
		parent::__construct($foo);
	}
}

class B3 extends A {
	// We don't call the parent constructor, so it's fine.
	public function __construct(public readonly int $myProp) {
	}
}

class B4 extends A {
	protected readonly string
		$foo,
		// report overriding non-promoted property as well.
		$nonPromotedProp;
	public function __construct() {
		$this->foo = 'xyz';
		$this->nonPromotedProp = 'bbb';
		parent::__construct(5);
	}
}

class B5 extends A {
	// non-promoted property overriding promoted property
	public readonly int $myProp;
	public function __construct() {
		$this->myProp = 7;
		parent::__construct(5);
	}
}

class B6 extends A {
	// This is fine - we don't call parent constructor;
	public readonly int $myProp;
	public function __construct() {
		$this->myProp = 5;
	}
}

class B7 extends A {
	// Call parent construtor indirectly
	public readonly int $myProp;
	public function __construct() {
		$this->myProp = 5;
		$this->foo();
	}

	private function foo(): void
	{
		A::__construct(5);
	}
}

class B8 extends A {
	// Don't get confused by prop declaration in anonymous class.
	public function __construct() {
		parent::__construct(5);
		$c = new class {
			public readonly int $myProp;
		};
	}
}

class B9 extends A {
	// Don't get confused by constructor call in anonymous class
	public readonly int $myProp;
	public function __construct() {
		$this->myProp = 5;
		$c = new class extends A {
			public function __construct()
			{
				parent::__construct(5);
			}
		};
	}
}

class B10 extends A {
	// Don't get confused by promoted properties in anonymous class
	public function __construct() {
		parent::__construct(5);
		$c = new class (5) {
			public function __construct(public readonly int $myProp)
			{
			}
		};
	}
}

class B11 extends A {
	public readonly int $myProp;
	public function __construct() {
		$this->myProp = 5;
		$c = new class ('aaa') extends A {
			// Detect redeclaration even inside anonymous classes.
			public function __construct(protected readonly string $nonPromotedProp)
			{
				parent::__construct(5);
			}
		};
	}
}
