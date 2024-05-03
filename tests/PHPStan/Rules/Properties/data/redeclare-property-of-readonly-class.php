<?php declare(strict_types = 1); // lint >= 8.2

namespace RedeclarePropertyOfReadonlyClass;

readonly class A {
	public function __construct(public int $promotedProp)
	{
	}
}

readonly class B1 extends A {
	// $promotedProp is written twice
	public function __construct(public int $promotedProp)
	{
		parent::__construct(5);
	}
}

readonly class B2 extends A {
	// Don't get confused by standard parameter with same name
	public function __construct(int $promotedProp)
	{
		parent::__construct($promotedProp);
	}
}

readonly class B3 extends A {
	// This is allowed, because we don't write to the property.
	public int $promotedProp;

	public function __construct()
	{
		parent::__construct(7);
	}
}

readonly class B4 extends A {
	// The second write is not from the constructor. It is an error, but it is handled by different rule.
	public int $promotedProp;

	public function __construct()
	{
		parent::__construct(7);
	}

	public function set(): void
	{
		$this->promotedProp = 7;
	}
}
