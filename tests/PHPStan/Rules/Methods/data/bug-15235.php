<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15235;

/**
 * @template T of object
 */
class A
{
	/**
	 * @param T $object
	 */
	public function __construct(public object $object) {}
}

class B
{
	/**
	 * @param A<covariant static> $a
	 */
	public function method(A $a): void {}

	/**
	 * @param A<static> $a
	 */
	public function invariant(A $a): void {}

	/**
	 * @param A<contravariant static> $a
	 */
	public function contravariant(A $a): void {}
}

class D
{
	public function inconsistent(B $b): void
	{
		$b->method(new A($b));
	}
}

function fromNew(): void
{
	$b = new B();
	$b->method(new A($b));
	$b->invariant(new A($b));
	$b->contravariant(new A($b));
}

function fromGetClass(B $b): void
{
	if (get_class($b) !== B::class) {
		return;
	}

	$b->method(new A($b));
}

/** @template-covariant T of object */
class Covariant
{
	/** @param T $object */
	public function __construct(public object $object) {}
}

/** @template-contravariant T of object */
class Contravariant
{
	/** @param T $object */
	public function set(object $object): void {}
}

class E
{
	/** @param Covariant<static> $c */
	public function declaredCovariant(Covariant $c): void {}

	/** @param Contravariant<static> $c */
	public function declaredContravariant(Contravariant $c): void {}

	/** @param \Traversable<int, static> $it */
	public function traversable(\Traversable $it): void {}

	/** @return Covariant<static> */
	public function makeCovariant(): Covariant
	{
		return new Covariant($this);
	}

	/** @return Wrapper<static> */
	public function makeWrapper(): Wrapper
	{
		return new Wrapper($this);
	}
}

class Consumer
{
	/** @param Covariant<E> $c */
	public function takesCovariant(Covariant $c): void {}

	/** @param Wrapper<contravariant E> $wrapper */
	public function takesContravariantWrapper(Wrapper $wrapper): void {}

	/** @param Wrapper<Covariant<E>> $wrapper */
	public function takesNested(Wrapper $wrapper): void {}
}

/**
 * @param Covariant<E> $covariant
 * @param Contravariant<E> $contravariant
 * @param \Traversable<int, E> $traversable
 */
function declaredVariances(Covariant $covariant, Contravariant $contravariant, \Traversable $traversable): void
{
	$e = new E();
	$e->declaredCovariant($covariant);
	$e->declaredContravariant($contravariant);
	$e->traversable($traversable);
}

function flavourOnTheArgumentSide(Consumer $consumer): void
{
	$e = new E();
	$consumer->takesCovariant($e->makeCovariant());
	$consumer->takesContravariantWrapper($e->makeWrapper());
}

/** @template T of object */
class Wrapper
{
	/** @param T $object */
	public function __construct(public object $object) {}
}

function nestedInGenericType(Consumer $consumer): void
{
	$e = new E();
	$consumer->takesNested(new Wrapper($e->makeCovariant()));
}

class Composite
{
	/** @param Covariant<static>|null $c */
	public function inUnion(?Covariant $c): void {}

	/** @param array<int, Covariant<static>> $c */
	public function inArray(array $c): void {}

	/** @param Covariant<Covariant<static>> $c */
	public function nested(Covariant $c): void {}

	/** @param Wrapper<Covariant<static>> $c */
	public function nestedInvariant(Wrapper $c): void {}
}

/**
 * @param Covariant<Composite> $covariant
 * @param array<int, Covariant<Composite>> $array
 * @param Covariant<Covariant<Composite>> $nested
 * @param Wrapper<Covariant<Composite>> $nestedInvariant
 */
function compositeShapes(Covariant $covariant, array $array, Covariant $nested, Wrapper $nestedInvariant): void
{
	$composite = new Composite();
	$composite->inUnion($covariant);
	$composite->inUnion(null);
	$composite->inArray($array);
	$composite->nested($nested);
	$composite->nestedInvariant($nestedInvariant);
}
