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

	/**
	 * @phpstan-pure
	 */
	public function getPure(): ?A {
		return $this->a;
	}
}

class HelloWorld
{
	public function testFails(): void
	{
		$a = new A(1);
		$b = new B(1, $a);

		$hasA = $b->getPure() !== null;

		if($hasA) {
			assertType('Bug9455\A', $b->getPure());
		}
	}

	public function testSucceeds(): void
	{
		$a = new A(1);
		$b = new B(1, $a);

		if($b->getPure() !== null) {
			assertType('Bug9455\A', $b->getPure());
		}
	}
}

class C {
	/**
	 * @phpstan-impure
	 */
	public function getImpure(): ?A {
		return rand(0, 1) ? new A(1) : null;
	}
}

class ImpureTest
{
	public function testImpureMethodNotNarrowed(): void
	{
		$c = new C();

		$hasA = $c->getImpure() !== null;

		if($hasA) {
			assertType('Bug9455\A|null', $c->getImpure());
		}
	}

	public function testImpureMethodInline(): void
	{
		$c = new C();

		if($c->getImpure() !== null) {
			assertType('Bug9455\A|null', $c->getImpure());
		}
	}
}

class D {
	public function getUnknownPurity(): ?A {
		return rand(0, 1) ? new A(1) : null;
	}
}

function doUnknownPurity(): void
{
	$d = new D();

	$hasA = $d->getUnknownPurity() !== null;

	if($hasA) {
		assertType('Bug9455\A|null', $d->getUnknownPurity());
	}
}
