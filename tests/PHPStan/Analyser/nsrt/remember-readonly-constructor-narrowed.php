<?php // lint >= 8.2

namespace RememberReadOnlyConstructor;

use function PHPStan\Testing\assertType;

class HelloWorldReadonlyProperty {
	private readonly int $i;

	public function __construct()
	{
		if (rand(0,1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}
	}

	public function doFoo() {
		assertType('4|10', $this->i);
	}
}

readonly class HelloWorldReadonlyClass {
	private int $i;

	public function __construct()
	{
		if (rand(0,1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}
	}

	public function doFoo() {
		assertType('4|10', $this->i);
	}
}


class HelloWorldRegular {
	private int $i;

	public function __construct()
	{
		if (rand(0,1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}
	}

	public function doFoo() {
		assertType('int', $this->i);
	}
}
