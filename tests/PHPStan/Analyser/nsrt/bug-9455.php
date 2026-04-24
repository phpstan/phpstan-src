<?php

namespace Bug9455;

use function PHPStan\Testing\assertType;

class A {
	public function __construct(private int $id) {}

	public function getId(): int
	{
		return $this->id;
	}
}

class B {
	public function __construct(private int $id, private ?A $a = null) {}

	public function getId(): int
	{
		return $this->id;
	}

	public function getA(): ?A
	{
		return $this->a;
	}
}

class HelloWorld
{

	public function testFails(B $b): void
	{
		$hasA = $b->getA() !== null;

		if ($hasA) {
			assertType(A::class, $b->getA());
			echo $b->getA()->getId();
		}
	}

	public function testSucceeds(B $b): void
	{
		if ($b->getA() !== null) {
			assertType(A::class, $b->getA());
			echo $b->getA()->getId();
		}
	}

}
