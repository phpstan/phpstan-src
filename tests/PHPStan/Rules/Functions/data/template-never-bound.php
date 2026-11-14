<?php declare(strict_types = 1);

namespace TemplateNeverBoundReturnType;

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function nested(int $t): int
{
	if ($t > 0) {
		if ($t >= 0) {
			echo 'x';
		} else {
			echo 'never';
		}

		return $t;
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
		echo 'negative';
	} elseif ($t >= 0) {
		echo 'non-negative';
	} else {
		echo 'never';
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
		echo 'true';
	} elseif ($t === false) {
		echo 'false';
	} else {
		echo 'never';
	}

	return $t;
}

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function stillReported(int $t, int $other): int
{
	if ($t > 0) {
		if ($t >= 0) {
			return $other;
		}
	}

	return $t;
}
