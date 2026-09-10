<?php

namespace PHPStan\Generics\InvalidReturn;

/**
 * @template T
 * @param T $a
 * @return T
 */
function invalidReturnA($a) {
	var_dump($a);

	return 1;
}

/**
 * @template T of \DateTimeInterface
 * @param T $a
 * @return T
 */
function invalidReturnB($a) {
	var_dump($a);

	return new \DateTime();
}

/**
 * @template T of \DateTime
 * @param T $a
 * @return T
 */
function invalidReturnC($a) {
	var_dump($a);

	return new \DateTime();
}
