<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug12964;

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
 * @template-covariant T
 */
interface A
{
	/**
	 * @var T
	 */
	public mixed $b { get; }
}

/**
 * @template-covariant T
 */
final class B
{
	/**
	 * @param T $data
	 */
	public function __construct(
		public private(set) mixed $data,
	) {}
}

/**
 * @template-covariant X
 */
class C {
	/** @var X */
	public private(set) mixed $a;

	/** @var In<X> */
	public private(set) mixed $b;

	/** @var Out<X> */
	public private(set) mixed $c;

	/** @var Invariant<X> */
	public private(set) mixed $d;
}

/**
 * @template-contravariant X
 */
class D {
	/** @var X */
	public private(set) mixed $a;

	/** @var In<X> */
	public private(set) mixed $b;

	/** @var Out<X> */
	public private(set) mixed $c;

	/** @var Invariant<X> */
	public private(set) mixed $d;
}

/**
 * @template-covariant X
 */
class E {
	/** @var X */
	public protected(set) mixed $a;

	/** @var In<X> */
	public protected(set) mixed $b;

	/** @var Out<X> */
	public protected(set) mixed $c;

	/** @var Invariant<X> */
	public protected(set) mixed $d;
}

/**
 * @template-covariant X
 */
interface F
{
	/** @var X */
	public mixed $a { get; }

	/** @var In<X> */
	public mixed $b { get; }

	/** @var Out<X> */
	public mixed $c { get; }

	/** @var Invariant<X> */
	public mixed $d { get; }
}

/**
 * @template-contravariant X
 */
interface G
{
	/** @var X */
	public mixed $a { get; }

	/** @var In<X> */
	public mixed $b { get; }

	/** @var Out<X> */
	public mixed $c { get; }

	/** @var Invariant<X> */
	public mixed $d { get; }
}

/**
 * @template-covariant X
 */
interface H
{
	/** @var X */
	public mixed $a { get; set; }
}
