<?php

namespace MethodSignatureVarianceContravariant;

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
 * @template-contravariant K
 */
class A {
	/**
	 * @param K $a
	 * @param In<K> $b
	 * @param In<In<K>> $c
	 * @param In<Out<K>> $d
	 * @param In<Out<Invariant<K>>> $e
	 * @param In<Invariant<In<K>>> $f
	 * @param Out<K> $g
	 * @param Out<Out<K>> $h
	 * @param Out<In<K>> $i
	 * @param Out<In<Invariant<K>>> $j
	 * @param Out<Invariant<Out<K>>> $k
	 * @param Invariant<K> $l
	 * @param Invariant<Invariant<K>> $m
	 * @param Invariant<Out<In<K>>> $n
	 * @return K
	 */
	function a($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l, $m, $n) {}

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
