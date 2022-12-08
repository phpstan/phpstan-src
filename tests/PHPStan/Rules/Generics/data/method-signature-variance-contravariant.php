<?php

namespace MethodSignatureVariance\Contravariant;

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
 * @template-contravariant X
 */
class C {
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param In<In<X>> $c
	 * @param In<Out<X>> $d
	 * @param In<Invariant<X>> $e
	 * @param Out<X> $f
	 * @param Out<In<X>> $g
	 * @param Out<Out<X>> $h
	 * @param Out<Invariant<X>> $i
	 * @param Invariant<X> $j
	 * @param Invariant<In<X>> $k
	 * @param Invariant<Out<X>> $l
	 */
	function a($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}

	/** @return X */
	function b() {}

	/** @return In<X> */
	function c() {}

	/** @return In<In<X>> */
	function d() {}

	/** @return In<Out<X>> */
	function e() {}

	/** @return In<Invariant<X>> */
	function f() {}

	/** @return Out<X> */
	function g() {}

	/** @return Out<In<X>> */
	function h() {}

	/** @return Out<Out<X>> */
	function i() {}

	/** @return Out<Invariant<X>> */
	function j() {}

	/** @return Invariant<X> */
	function k() {}

	/** @return Invariant<In<X>> */
	function l() {}

	/** @return Invariant<Out<X>> */
	function m() {}

	/** @return X */
	private function n() {}
}
