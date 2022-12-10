<?php // lint >= 8.0

namespace PropertyVariance\Promoted;

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
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param Out<X> $c
	 * @param Invariant<X> $d
	 * @param X $e
	 * @param In<X> $f
	 * @param Out<X> $g
	 * @param Invariant<X> $h
	 */
	public function __construct(
		public $a,
		public $b,
		public $c,
		public $d,
		private $e,
		private $f,
		private $g,
		private $h,
	) {}
}

/**
 * @template-covariant X
 */
class B {
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param Out<X> $c
	 * @param Invariant<X> $d
	 * @param X $e
	 * @param In<X> $f
	 * @param Out<X> $g
	 * @param Invariant<X> $h
	 */
	public function __construct(
		public $a,
		public $b,
		public $c,
		public $d,
		private $e,
		private $f,
		private $g,
		private $h,
	) {}
}

/**
 * @template-contravariant X
 */
class C {
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param Out<X> $c
	 * @param Invariant<X> $d
	 * @param X $e
	 * @param In<X> $f
	 * @param Out<X> $g
	 * @param Invariant<X> $h
	 */
	public function __construct(
		public $a,
		public $b,
		public $c,
		public $d,
		private $e,
		private $f,
		private $g,
		private $h,
	) {}
}
