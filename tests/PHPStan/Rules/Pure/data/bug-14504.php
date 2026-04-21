<?php declare(strict_types = 1);

namespace Bug14504;

/**
 * @phpstan-pure
 * @template T of int|string
 * @param T $val
 * @return string
 */
function stringCast($val): string {
	return (string)$val;
}

/**
 * @phpstan-pure
 * @template T of int|string
 * @param T $val
 * @return string
 */
function stringConcat($val): string {
	return '' . $val;
}

/**
 * @phpstan-pure
 * @template T of int|string
 * @param T $val
 * @return string
 */
function stringInterpolation($val): string {
	return "$val";
}

/**
 * @phpstan-pure
 * @template T of int|float|bool
 * @param T $val
 * @return string
 */
function nonStringNonObjectCast($val): string {
	return (string)$val;
}
