<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14511;

/**
 * @phpstan-pure
 * @template T of mixed
 * @param T $val
 */
function testStringCast(mixed $val): ?string {
	if (is_int($val)) {
		return (string) $val;
	}
	return null;
}

/**
 * @phpstan-pure
 * @template T of mixed
 * @param T $val
 */
function testStringConcat(mixed $val): ?string {
	if (is_int($val)) {
		return 'value: ' . $val;
	}
	return null;
}

/**
 * @phpstan-pure
 * @template T of mixed
 * @param T $val
 */
function testFloatCast(mixed $val): ?string {
	if (is_float($val)) {
		return (string) $val;
	}
	return null;
}

/**
 * @phpstan-pure
 * @template T of mixed
 * @param T $val
 */
function testBoolCast(mixed $val): ?string {
	if (is_bool($val)) {
		return (string) $val;
	}
	return null;
}

/**
 * @phpstan-pure
 * @template T of mixed
 * @param T $val
 */
function testStringVal(mixed $val): ?string {
	if (is_string($val)) {
		return (string) $val;
	}
	return null;
}

/**
 * @phpstan-pure
 * @template T of mixed
 * @param T $val
 */
function testEmptyNonArray(mixed $val): ?string {
	if (empty($val) && !\is_array($val)) {
		return (string) $val;
	}
	return null;
}
