<?php

namespace MethodSignatureVariance\Constructor;

/** @template-contravariant T */
interface In {
}

/** @template-covariant T */
interface Out {
}

/** @template T */
interface Invariant {
}

/** @template X */
class A {
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
	function __construct($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}
}

/** @template-covariant X */
class B {
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
	function __construct($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}
}

/** @template-contravariant X */
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
	function __construct($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l) {}
}
