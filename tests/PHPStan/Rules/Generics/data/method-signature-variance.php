<?php

namespace MethodSignatureVariance;

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
 * @template K
 */
class A {
	/**
	 * @param K $a
	 * @param In<K> $b
	 * @param In<In<K>> $c
	 * @param In<Out<K>> $d
	 * @param In<Out<Invariant<K>>> $e
	 * @param Out<K> $f
	 * @param Out<Out<K>> $g
	 * @param Out<In<K>> $h
	 * @param Out<In<Invariant<K>>> $i
	 * @param Invariant<K> $j
	 * @param Invariant<Invariant<K>> $k
	 * @param Invariant<Out<In<K>>> $l
	 * @return K
	 */
	function a($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}

	/** @return In<K> */
	function b() {}

	/** @return In<In<K>> */
	function c() {}

	/** @return In<Out<K>> */
	function d() {}

	/** @return In<Out<Invariant<K>>> */
	function e() {}

	/** @return Out<K> */
	function f() {}

	/** @return Out<Out<K>> */
	function g() {}

	/** @return Out<In<K>> */
	function h() {}

	/** @return Out<In<Invariant<K>>> */
	function i() {}

	/** @return Invariant<K> */
	function j() {}

	/** @return Invariant<Invariant<K>> */
	function k() {}

	/** @return Invariant<In<Out<K>>> */
	function l() {}

	/** @return Invariant<Out<In<K>>> */
	function m() {}
}

/**
 * @template-contravariant K
 */
class B {
	/**
	 * @param K $a
	 * @param In<K> $b
	 * @param In<In<K>> $c
	 * @param In<Out<K>> $d
	 * @param In<Out<Invariant<K>>> $e
	 * @param Out<K> $f
	 * @param Out<Out<K>> $g
	 * @param Out<In<K>> $h
	 * @param Out<In<Invariant<K>>> $i
	 * @param Invariant<K> $j
	 * @param Invariant<Invariant<K>> $k
	 * @param Invariant<Out<In<K>>> $l
	 * @return K
	 */
	function a($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}

	/** @return In<K> */
	function b() {}

	/** @return In<In<K>> */
	function c() {}

	/** @return In<Out<K>> */
	function d() {}

	/** @return In<Out<Invariant<K>>> */
	function e() {}

	/** @return Out<K> */
	function f() {}

	/** @return Out<Out<K>> */
	function g() {}

	/** @return Out<In<K>> */
	function h() {}

	/** @return Out<In<Invariant<K>>> */
	function i() {}

	/** @return Invariant<K> */
	function j() {}

	/** @return Invariant<Invariant<K>> */
	function k() {}

	/** @return Invariant<In<Out<K>>> */
	function l() {}

	/** @return Invariant<Out<In<K>>> */
	function m() {}
}

/**
 * @template-covariant K
 */
class C {
	/**
	 * @param K $a
	 * @param In<K> $b
	 * @param In<In<K>> $c
	 * @param In<Out<K>> $d
	 * @param In<Out<Invariant<K>>> $e
	 * @param Out<K> $f
	 * @param Out<Out<K>> $g
	 * @param Out<In<K>> $h
	 * @param Out<In<Invariant<K>>> $i
	 * @param Invariant<K> $j
	 * @param Invariant<Invariant<K>> $k
	 * @param Invariant<Out<In<K>>> $l
	 * @return K
	 */
	function a($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}

	/** @return In<K> */
	function b() {}

	/** @return In<In<K>> */
	function c() {}

	/** @return In<Out<K>> */
	function d() {}

	/** @return In<Out<Invariant<K>>> */
	function e() {}

	/** @return Out<K> */
	function f() {}

	/** @return Out<Out<K>> */
	function g() {}

	/** @return Out<In<K>> */
	function h() {}

	/** @return Out<In<Invariant<K>>> */
	function i() {}

	/** @return Invariant<K> */
	function j() {}

	/** @return Invariant<Invariant<K>> */
	function k() {}

	/** @return Invariant<In<Out<K>>> */
	function l() {}

	/** @return Invariant<Out<In<K>>> */
	function m() {}
}
