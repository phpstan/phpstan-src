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
	private string $class;
	private string $interface;
	private string $enum;
	private string $trait;

	public function __construct(string $class, string $interface, string $enum, string $trait)
	{
		if (rand(0,1)) {
			$this->i = 4;
		} else {
			$this->i = 10;
		}

		if (!class_exists($class)) {
			throw new \LogicException();
		}
		$this->class = $class;

		if (!interface_exists($interface)) {
			throw new \LogicException();
		}
		$this->interface = $interface;

		if (!enum_exists($enum)) {
			throw new \LogicException();
		}
		$this->enum = $enum;

		if (!trait_exists($trait)) {
			throw new \LogicException();
		}
		$this->trait = $trait;
	}

	public function doFoo() {
		assertType('4|10', $this->i);
		assertType('class-string', $this->class);
		assertType('class-string', $this->interface);
		assertType('class-string<UnitEnum>', $this->enum);
		assertType('class-string', $this->trait);
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

class HelloWorldReadonlyPropertySometimesThrowing {
	private readonly int $i;

	public function __construct()
	{
		if (rand(0,1)) {
			$this->i = 4;

			return;
		} elseif (rand(10,100)) {
			$this->i = 10;
			return;
		} else {
			$this->i = 20;
		}

		throw new \LogicException();
	}

	public function doFoo() {
		assertType('4|10', $this->i);
	}
}
