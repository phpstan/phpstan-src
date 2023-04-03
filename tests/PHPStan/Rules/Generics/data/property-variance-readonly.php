<?php // lint >= 8.1

namespace PropertyVariance\ReadOnly;

/** @template-contravariant T */
interface In {
}

/** @template-covariant T */
interface Out {
}

/** @template T */
interface Invariant {
}

/**
 * @template X
 */
class A {
	/** @var X */
	public readonly mixed $a;

	/** @var In<X> */
	public readonly mixed $b;

	/** @var Out<X> */
	public readonly mixed $c;

	/** @var Invariant<X> */
	public readonly mixed $d;

	/** @var Invariant<X> */
	private readonly mixed $e;
}

/**
 * @template-covariant X
 */
class B {
	/** @var X */
	public readonly mixed $a;

	/** @var In<X> */
	public readonly mixed $b;

	/** @var Out<X> */
	public readonly mixed $c;

	/** @var Invariant<X> */
	public readonly mixed $d;

	/** @var Invariant<X> */
	private readonly mixed $e;
}

/**
 * @template-contravariant X
 */
class C {
	/** @var X */
	public readonly mixed $a;

	/** @var In<X> */
	public readonly mixed $b;

	/** @var Out<X> */
	public readonly mixed $c;

	/** @var Invariant<X> */
	public readonly mixed $d;

	/** @var Invariant<X> */
	private readonly mixed $e;
}

/**
 * @template-contravariant X
 */
class D {
	/**
	 * @param X $a
	 * @param X $b
	 */
	public function __construct(
		public readonly mixed $a,
		private readonly mixed $b,
	) {}
}
