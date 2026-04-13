<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9455;

use function PHPStan\Testing\assertType;

class A {
	public function __construct(private int $id){}

	public function getId(): int {
		return $this->id;
	}
}

class B {
	public function __construct(private int $id, private ?A $a = null){}

	public function getId(): int {
		return $this->id;
	}

	public function getA(): ?A {
		return $this->a;
	}
}

class HelloWorld
{
	public function testFails(): void
	{
		$a = new A(1);
		$b = new B(1, $a);

		$hasA = $b->getA() !== null;

		if($hasA) {
			assertType('Bug9455\A', $b->getA());
		}
	}

	public function testSucceeds(): void
	{
		$a = new A(1);
		$b = new B(1, $a);

		if($b->getA() !== null) {
			assertType('Bug9455\A', $b->getA());
		}
	}
}
