<?php

namespace PHPStan\Generics\Variance;

/** @template T */
interface InvariantIter {
	/** @return T */
	public function next();
}


/** @template-covariant T */
interface Iter {
	/** @return T */
	public function next();
}

/** @param InvariantIter<\DateTime> $it */
function acceptInvariantIterOfDateTime($it): void {
}

/** @param InvariantIter<\DateTimeInterface> $it */
function acceptInvariantIterOfDateTimeInterface($it): void {
}

/** @param Iter<\DateTime> $it */
function acceptIterOfDateTime($it): void {
}

/** @param Iter<\DateTimeInterface> $it */
function acceptIterOfDateTimeInterface($it): void {
}

/** @template T */
interface Invariant {
}

/** @template-covariant T */
interface Out {
}

/** @template-covariant T */
interface Foo {
	/**
	 * @param T $a
	 * @param Invariant<T> $b
	 * @param Out<T> $c
	 * @param array<T> $d
	 * @param Out<Out<Out<T>>> $e
	 * @return T
	 */
	function x($a, $b, $c, $d, $e);
}

/**
 * @template-covariant T
 * @extends Invariant<T>
 * @extends Out<T>
 */
interface Bar extends Invariant, Out {
}

/**
 * @template T
 * @extends Invariant<T>
 * @extends Out<T>
 */
interface Baz extends Invariant, Out {
}

/**
 * @template-covariant T
 * @implements Invariant<T>
 * @implements Out<T>
 */
class Qux implements Invariant, Out {
}

/**
 * @template T
 */
class Quux {
}

/**
 * @template-covariant T
 * @extends Quux<T>
 */
class Quuz extends Quux {
}

/**
 * @template-covariant T
 * @param T $a
 * @param Invariant<T> $b
 * @param Out<T> $c
 * @param array<T> $d
 * @param Out<Out<Out<T>>> $e
 * @return T
 */
function x($a, $b, $c, $d, $e) {
	return $a;
}

/**
 * @template-covariant T
 * @return Out<T>
 */
function returnOut() {
	throw new \Exception();
}

/**
 * @template-covariant T
 * @return Invariant<T>
 */
function returnInvariant() {
	throw new \Exception();
}

/** @template-covariant T of object */
interface CovariantOfObject {
	/** @param T $v */
	function set($v): void;
}

/**
 * @template-covariant T
 * @template U
 */
class ConstructorAndStatic {

	/** @var mixed */
	private $data;

	/**
	 * @param T $t
	 * @param U $u
	 * @param Invariant<T> $v
	 * @param Out<T> $w
	 */
	public function __construct($t, $u, $v, $w) {
		$this->data = [$t, $u, $v, $w];
	}

	/**
	 * @param T $t
	 * @param U $u
	 * @param Invariant<T> $v
	 * @param Out<T> $w
	 * @return Static<T, U>
	 */
	public static function create($t, $u, $v, $w) {
		return new self($t, $u, $v, $w);
	}
}

/**
 * @param Iter<\DateTime> $itOfDateTime
 * @param InvariantIter<\DateTime> $invariantItOfDateTime
 */
function test($itOfDateTime, $invariantItOfDateTime): void {
	acceptInvariantIterOfDateTime($invariantItOfDateTime);
	acceptInvariantIterOfDateTimeInterface($invariantItOfDateTime);

	acceptIterOfDateTime($itOfDateTime);
	acceptIterOfDateTimeInterface($itOfDateTime);
}
