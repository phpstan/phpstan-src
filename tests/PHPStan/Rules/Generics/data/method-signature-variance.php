<?php

namespace MethodSignatureVariance;

/** @template-covariant T */
interface Out {
}

/** @template T */
interface Invariant {
}

/**
 * @template-covariant T
 * @template-covariant W of \DateTimeInterface
 */
class C {
	/**
	 * @param Out<T> $a
	 * @param Invariant<T> $b
	 * @param T $c
	 * @param W $d
	 * @return T
	 */
	function a($a, $b, $c, $d) {
		return $c;
	}
	/**
	 * @template-covariant U
	 * @param Out<U> $a
	 * @param Invariant<U> $b
	 * @param U $c
	 * @return U
	 */
	function b($a, $b, $c) {
		return $c;
	}
}
