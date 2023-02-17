<?php declare(strict_types = 1);

namespace Generics\Bug8609;

/**
 * @template T of int
 */
class A {
	/** @var T */
    private $value;

    /** @param T $value */
	public function __construct($value) {
        $this->value = $value;
	}

	/**
	 * @template C of int
	 *
	 * @param C $coefficient
	 *
	 * @return (
	 *  T is positive-int
	 *      ? (C is positive-int ? positive-int : negative-int)
	 *      : T is negative-int
	 *          ? (C is positive-int ? negative-int : positive-int)
	 *          : (T is 0 ? 0 : int)
	 *      )
	 * )
	 */
	public function multiply(int $coefficient): int {
		return $this->value * $coefficient;
	}
}
