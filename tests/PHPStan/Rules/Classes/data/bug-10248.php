<?php // lint >=8.0

namespace Bug10248;

class A {
	public function __construct(DateTimeInterface|float $value) {
		var_dump($value);
	}
}

class B {
	public function __construct(float $value) {
		var_dump($value);
	}
}

/**
 * @return int
 */
function getInt(): int{return 1;}

/**
 * @return int<0, max>
 */
function getRangeInt(): int{return 1;}

new A(123);
new A(getInt());
new A(getRangeInt());

new B(getRangeInt());
