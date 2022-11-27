<?php

namespace MethodSignatureVariance\Invariant;

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
class C {
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param In<In<X>> $c
	 * @param In<Out<X>> $d
	 * @param In<Invariant<X> $e
	 * @param Out<X> $f
	 * @param Out<In<X>> $g
	 * @param Out<Out<X>> $h
	 * @param Out<Invariant<X> $i
	 * @param Invariant<X> $j
	 * @param Invariant<In<X>> $k
	 * @param Invariant<Out<X>> $l
	 * @return X
	 */
	function a($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}

	/** @return In<X> */
	function b() {}

	/** @return In<In<X>>*/
	function c() {}

	/** @return In<Out<X>>*/
	function d() {}

	/** @return In<Invariant<X> */
	function e() {}

	/** @return Out<X> */
	function f() {}

	/** @return Out<In<X>>*/
	function g() {}

	/** @return Out<Out<X>>*/
	function h() {}

	/** @return Out<Invariant<X> */
	function i() {}

	/** @return Invariant<X> */
	function j() {}

	/** @return Invariant<In<X>>*/
	function k() {}

	/** @return Invariant<Out<X>>*/
	function l() {}
}
