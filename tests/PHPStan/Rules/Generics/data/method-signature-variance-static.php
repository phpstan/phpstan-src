<?php

namespace MethodSignatureVariance\StaticMethod;

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
	 * @param Out<X> $c
	 */
	static function a($a, $b, $c) {}

	/** @return X */
	static function b() {}

	/** @return In<X> */
	static function c() {}

	/** @return Out<X> */
	static function d() {}
}

/** @template-covariant X */
class B {
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param Out<X> $c
	 */
	static function a($a, $b, $c) {}

	/** @return X */
	static function b() {}

	/** @return In<X> */
	static function c() {}

	/** @return Out<X> */
	static function d() {}
}

/** @template-contravariant X */
class C {
	/**
	 * @param X $a
	 * @param In<X> $b
	 * @param Out<X> $c
	 */
	static function a($a, $b, $c) {}

	/** @return X */
	static function b() {}

	/** @return In<X> */
	static function c() {}

	/** @return Out<X> */
	static function d() {}
}
