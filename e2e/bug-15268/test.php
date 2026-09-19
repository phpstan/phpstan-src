<?php declare(strict_types = 1);

use function PHPStan\Testing\assertType;

/**
 * @param int|false $priority
 * @return ($priority is int ? bool : int|false)
 */
function leaf($priority = false) {
	return is_int($priority) ? true : 5;
}

/**
 * @param int|false $priority
 * @return ($priority is int ? bool : int|false)
 */
function wrapper($priority = false) {
	// $priority is int|false, so the condition selects neither branch and the result
	// should be their union. This assertion passes when PHPStan runs on PHP 8.2, and
	// fails with "Expected type bool|int, actual: bool" on PHP 8.3, 8.4 and 8.5.
	assertType('bool|int', leaf($priority));

	// Because the type above lost its int, PHP 8.3+ additionally reports
	// "Function wrapper() never returns int so it can be removed from the return type."
	// It does return int: leaf(false) returns 5. No such error on PHP 8.2.
	return leaf($priority);
}

// These hold on every PHP version: when the argument type selects a branch, the
// conditional resolves correctly.
assertType('int|false', leaf());
assertType('bool', leaf(10));
assertType('int|false', wrapper());

// Playground result on PHP 8.2 (its own runtime): No errors.
// Run locally on PHP 8.3+: two errors, the failed assertion and return.unusedType.
