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
		// We can't detect this at the moment.
		$nonPromotedProp;
	public function __construct() {
		$this->foo = 'xyz';
		$this->nonPromotedProp = 'bbb';
		parent::__construct(5);
	}
}

class B5 extends A {
	// Error: we can't both write the property ourselves and call the parent constructor.
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
	// Call parent constructor indirectly.
	public function __construct(public readonly int $myProp) {
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
			public function __construct(public readonly int $myProp)
			{
			}
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
	// This is fine - we don't call the parent constructor.
	public readonly int $myProp;
	public function __construct() {
		$this->myProp = 5;
		$c = new class ('aaa') extends A {
			// Detect redeclaration even inside anonymous classes.
			public function __construct(public readonly int $myProp)
			{
				parent::__construct(5);
			}
		};
	}
}

class A12 {
	public function __construct(public readonly int $aProp)
	{
	}
}

class B12 extends A12 {
	public function __construct(public readonly int $bProp)
	{
		parent::__construct(15);
	}
}

class C12 extends B12 {
	// This is OK, because we call A12's constructor, not B12's.
	public function __construct(public readonly int $bProp) {
		A12::__construct(15);
	}
}

class B12_1 extends A12 {
	public function __construct(public readonly int $bProp)
	{
		parent::__construct(15);
	}
}

class C12_1 extends B12_1 {
	// This is an error, but we can't detect it at the moment.
	public function __construct(public readonly int $aProp) {
		parent::__construct(15);
	}
}

class A13 {
	public function __construct(private readonly int $privateProp)
	{
	}
}

class B13 extends A13 {
	// This is OK, A's prop is private
	public function __construct(public readonly int $privateProp)
	{
		parent::__construct(15);
	}
}

class B14 {
	public function __construct(public readonly int $myProp) {
		// Don't get confused by same property in non-parent's constructor.
		A::__construct(7);
	}
}

class B15 extends A {
	public function __construct(public readonly int $myProp) {
		self:foo();
	}

	public static function foo(): void
	{
		// Don't get confused by calling the parent constructor from static scope.
		parent::__construct(7);
	}
}

class B16 extends A {
	public readonly int $myProp;

	public function __construct(A $other) {
		// Don't get confused by calling the constructor on other object.
		$other::__construct(7);
		$other->__construct(7);
	}
}

class A17 {
	public function __construct(public readonly int $aProp)
	{
	}
}

class B17 extends A17 {
	public function __construct()
	{
	}
}

class C17 extends B17 {
	// Error: $aProp may be unassigned, because B's constructor may not call A's
	public readonly int $aProp;

	public function __construct() {
		parent::__construct();
	}
}

class A18 {
	public function __construct(private readonly int $aProp)
	{
	}
}

class B18 extends A18 {
	// Make surer that we don't get confused by parent's private property.
	public readonly int $aProp;

	public function __construct()
	{
		parent::__construct(7);
	}
}

class A19 {
	public function __construct(public int $prop1, public int $prop2)
	{
	}
}

class B19 extends A19 {
	public int $prop1;
	public int $prop2;

	public function __construct()
	{
		if (rand()) {
			parent::__construct(5, 6);
		} else {
			$this->prop1 = 7;
		}

		// Error: this may not be assigned
		var_dump($this->prop2);
	}
}
