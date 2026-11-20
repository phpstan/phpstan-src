<?php declare(strict_types = 1);

namespace TemplateNeverBound;

use function PHPStan\Testing\assertType;

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function nested(int $t): int
{
	if ($t > 0) {
		if ($t >= 0) {
			assertType('T of int<1, max> (function TemplateNeverBound\nested(), argument)', $t);
		} else {
			assertType('*NEVER*', $t);
		}

		assertType('T of int<1, max> (function TemplateNeverBound\nested(), argument)', $t);
	}

	return $t;
}

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function chain(int $t): int
{
	if ($t < 0) {
		assertType('T of int<min, -1> (function TemplateNeverBound\chain(), argument)', $t);
	} elseif ($t >= 0) {
		assertType('T of int<0, max> (function TemplateNeverBound\chain(), argument)', $t);
	} else {
		assertType('*NEVER*', $t);
	}

	return $t;
}

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function afterMerge(int $t): int
{
	if ($t < 0) {
		echo 'negative';
	}

	if ($t >= 2 && $t <= 2) {
		assertType('T of 2 (function TemplateNeverBound\afterMerge(), argument)', $t);
	}

	return $t;
}

/**
 * @template T of bool
 * @param T $t
 * @return T
 */
function boolean(bool $t): bool
{
	if ($t === true) {
		assertType('T of bool (function TemplateNeverBound\boolean(), argument)&true', $t);
	} elseif ($t === false) {
		assertType('T of false (function TemplateNeverBound\boolean(), argument)&false', $t);
	} else {
		assertType('*NEVER*', $t);
	}

	return $t;
}

/**
 * @template T of string
 * @param T $t
 * @return T
 */
function str(string $t): string
{
	if (is_string($t)) {
		assertType('T of string (function TemplateNeverBound\str(), argument)', $t);
	} else {
		assertType('*NEVER*', $t);
	}

	return $t;
}
