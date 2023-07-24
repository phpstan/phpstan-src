<?php

namespace Bug6175;

class A {}
class B extends A {}
class C extends B {}

trait RefsTrait
{
	/**
	 * @return B|C
	 */
	public function foo()
	{
		return new A(); // @phpstan-ignore-line
	}

	public function dumpType(): void
	{
		\PHPStan\dumpType($this->foo());
	}
}

class Model
{
	use RefsTrait;

	/**
	 * @return B|C
	 */
	public function foo2()
	{
		return new A(); // @phpstan-ignore-line
	}
}
