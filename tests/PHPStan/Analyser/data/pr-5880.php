<?php // lint >= 8.0

declare(strict_types = 1);

namespace PR5880EndlessRecursion;

class A {
	public function a(): void {}
}
class B {}

/** @template-covariant T of A|B = A|B */
interface FooInterface
{
	/**
	 * @phpstan-assert-if-true static<A> $this
	 */
	public function isA(): bool;

	/** @return T */
	public function get(): A|B;
}

/**
 * @template-covariant T of A|B = A|B
 * @implements FooInterface<T>
 */
abstract class Foo implements FooInterface
{
	public function other(): void
	{
		if ($this->isA()) {
			$this->get()->a();
		}
	}

}
