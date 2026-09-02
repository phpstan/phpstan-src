<?php declare(strict_types = 1);

namespace DeepBooleanAndChain;

use function PHPStan\Testing\assertType;

/**
 * Chains longer than BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH take the flattened path in
 * BooleanAndHandler, both for the truthy side and - the De Morgan counterpart - the falsey
 * side. The narrowing must match what the recursive path produces for a short chain.
 *
 * @param 1|2|3|4|5|6|7|8 $x
 */
function deepChain(int $x): void
{
	if ($x !== 1 && $x !== 2 && $x !== 3 && $x !== 4 && $x !== 5 && $x !== 6 && $x !== 7) {
		assertType('8', $x);
	} else {
		assertType('1|2|3|4|5|6|7', $x);
	}

	assertType('1|2|3|4|5|6|7|8', $x);
}

/** @param 1|2|3 $x */
function shortChain(int $x): void
{
	// short enough to stay on the recursive path, for comparison
	if ($x !== 1 && $x !== 2) {
		assertType('3', $x);
	} else {
		assertType('1|2', $x);
	}
}

/**
 * @param 1|2|3|4|5|6|7|8 $x
 */
function deepChainNegated(int $x): void
{
	if (!($x !== 1 && $x !== 2 && $x !== 3 && $x !== 4 && $x !== 5 && $x !== 6 && $x !== 7)) {
		assertType('1|2|3|4|5|6|7', $x);
	} else {
		assertType('8', $x);
	}
}

/**
 * Comparing the chain against a boolean puts it in a mixed truthy-and-false context, which
 * must not be handled as if it were a plain truthy one - the chain being false does not make
 * every arm false.
 *
 * @param 1|2|3|4|5|6|7|8 $x
 */
function deepChainComparedToTrue(int $x): void
{
	if (($x !== 1 && $x !== 2 && $x !== 3 && $x !== 4 && $x !== 5 && $x !== 6 && $x !== 7) !== true) {
		assertType('1|2|3|4|5|6|7', $x);
	} else {
		assertType('8', $x);
	}
}

/** @param 1|2|3|4|5|6|7|8 $x */
function deepChainComparedToFalse(int $x): void
{
	if (($x !== 1 && $x !== 2 && $x !== 3 && $x !== 4 && $x !== 5 && $x !== 6 && $x !== 7) === false) {
		assertType('1|2|3|4|5|6|7', $x);
	} else {
		assertType('8', $x);
	}
}

/**
 * A deep chain of mixed arms: the falsey side cannot narrow the subject to a single value,
 * so the else branch keeps the declared type.
 *
 * @param 1|2|3|4|5|6|7|8 $x
 */
function deepChainMixed(int $x, bool $b): void
{
	if ($x !== 1 && $x !== 2 && $x !== 3 && $b && $x !== 4 && $x !== 5 && $x !== 6) {
		assertType('7|8', $x);
		assertType('true', $b);
	} else {
		assertType('1|2|3|4|5|6|7|8', $x);
		assertType('bool', $b);
	}
}

/**
 * @param non-empty-string|null $s
 */
function deepChainIsset(?string $s, ?int $i, ?float $f, ?bool $bo, ?object $o): void
{
	if ($s !== null && $i !== null && $f !== null && $bo !== null && $o !== null) {
		assertType('non-empty-string', $s);
		assertType('int', $i);
		assertType('float', $f);
		assertType('bool', $bo);
		assertType('object', $o);
	} else {
		assertType('non-empty-string|null', $s);
		assertType('int|null', $i);
	}
}
