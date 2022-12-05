<?php

namespace GenericVarianceCall;

class A {}
class B extends A {}
class C extends B {}

/** @template T */
class Invariant {}

/** @template-covariant T */
class Covariant {}

/** @template-contravariant T */
class Contravariant {}

class Foo
{
	/**
	 * @param Invariant<B> $param
	 */
	public function invariant(Invariant $param): void
	{
	}

	/**
	 * @param Covariant<B> $param
	 */
	public function covariant(Covariant $param): void
	{
	}

	/**
	 * @param Contravariant<B> $param
	 */
	public function contravariant(Contravariant $param): void
	{
	}

	public function testInvariant(): void
	{
		/** @var Invariant<A> $invariantA */
		$invariantA = new Invariant();
		$this->invariant($invariantA);

		/** @var Invariant<B> $invariantB */
		$invariantB = new Invariant();
		$this->invariant($invariantB);

		/** @var Invariant<C> $invariantC */
		$invariantC = new Invariant();
		$this->invariant($invariantC);
	}

	public function testCovariant(): void
	{
		/** @var Covariant<A> $covariantA */
		$covariantA = new Covariant();
		$this->covariant($covariantA);

		/** @var Covariant<B> $covariantB */
		$covariantB = new Covariant();
		$this->covariant($covariantB);

		/** @var Covariant<C> $covariantC */
		$covariantC = new Covariant();
		$this->covariant($covariantC);
	}

	public function testContravariant(): void
	{
		/** @var Contravariant<A> $contravariantA */
		$contravariantA = new Contravariant();
		$this->contravariant($contravariantA);

		/** @var Contravariant<B> $contravariantB */
		$contravariantB = new Contravariant();
		$this->contravariant($contravariantB);

		/** @var Contravariant<C> $contravariantC */
		$contravariantC = new Contravariant();
		$this->contravariant($contravariantC);
	}
}
