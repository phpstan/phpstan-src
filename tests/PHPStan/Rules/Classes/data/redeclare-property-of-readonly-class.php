<?php declare(strict_types = 1); // lint >= 8.2

namespace RedeclarePropertyOfReadonlyClass;

readonly class A {
	protected int $nonPromotedProp;
	public function __construct(public int $promotedProp)
	{
		$this->nonPromotedProp = 7;
	}
}

readonly class B1 extends A {
	// $nonPromotedProp is written twice
	public function __construct(public int $nonPromotedProp)
	{
		parent::__construct(5);
	}
}

readonly class B2 extends A {
	// Don't get confused by standard parameter with same name
	public function __construct(int $nonPromotedProp)
	{
		parent::__construct($nonPromotedProp);
	}
}
