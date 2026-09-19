<?php declare(strict_types = 1);

namespace Bug15268;

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

function nested(): void
{
	// the argument is the else branch of leaf()'s return type itself
	$priority = leaf(false);
	assertType('int|false', $priority);
	assertType('bool|int', leaf($priority));
}

/**
 * @param int|false $p
 * @return ($p is int ? bool : false|int)
 */
function elseFalseInt($p = false) {
	return is_int($p) ? true : 5;
}

/**
 * @param int|false $p
 * @return ($p is int ? int|false : bool)
 */
function thenIntFalse($p = false) {
	return is_int($p) ? 5 : true;
}

/**
 * @param int|false $p
 * @return ($p is false ? int|false : string)
 */
function thenIntFalseNarrowable($p = false) {
	return $p === false ? 5 : 'foo';
}

/**
 * @template T
 * @param T $p
 * @return ($p is int ? bool : int|false)
 */
function template($p) {
	return is_int($p) ? true : 5;
}

/**
 * @template T
 * @param T $p
 * @return (T is int ? bool : int|false)
 */
function templateSubject($p) {
	return is_int($p) ? true : 5;
}

/**
 * @param int|false $p
 * @param false|int $q
 */
function probe($p, $q): void
{
	assertType('bool|int', leaf($p));
	assertType('bool|int', leaf($q));
	assertType('bool|int', elseFalseInt($p));
	assertType('bool|int', elseFalseInt($q));
	assertType('bool|int', thenIntFalse($p));
	assertType('int|string|false', thenIntFalseNarrowable($p));
	assertType('bool|int', template($p));
	assertType('bool|int', templateSubject($p));
}
