<?php declare(strict_types = 1);

namespace Bug14566;

use function PHPStan\Testing\assertType;

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooNestedIfs(array $test): void {
	if (isset($test['hi'])) {
		if (is_string($test['hi'])) {
			return;
		}
	}
	assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooCombinedAnd(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooCombinedAndAssign(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	$test['hi'][] = 42;
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooBooleanOrDual(array $test): void {
	if (!isset($test['hi']) || !is_string($test['hi'])) {
		assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
		return;
	}
	assertType("array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 42}|array{hi: array{0: 42, 1?: 42}} $testIsArray
 */
function fooIsArray(array $testIsArray): void {
	if (isset($testIsArray['hi']) && is_array($testIsArray['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 42}", $testIsArray);
}

/**
 * @param array{}|array{hi: 42}|array{hi: 'hello'} $testIsInt
 */
function fooIsInt(array $testIsInt): void {
	if (isset($testIsInt['hi']) && is_int($testIsInt['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $testIsInt);
}

/**
 * @param array{}|array{hi: 42}|array{hi: 1.5} $testIsFloat
 */
function fooIsFloat(array $testIsFloat): void {
	if (isset($testIsFloat['hi']) && is_float($testIsFloat['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 42}", $testIsFloat);
}

/**
 * @param array{}|array{hi: true}|array{hi: 'hello'} $testIsBool
 */
function fooIsBool(array $testIsBool): void {
	if (isset($testIsBool['hi']) && is_bool($testIsBool['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $testIsBool);
}

/**
 * @param array{}|array{val: 'hello'}|array{val: array{0: 42}} $testArrayKeyExists
 */
function fooArrayKeyExists(array $testArrayKeyExists): void {
	if (array_key_exists('val', $testArrayKeyExists) && is_string($testArrayKeyExists['val'])) {
		return;
	}
	assertType("array{}|array{val: array{42}}", $testArrayKeyExists);
}
