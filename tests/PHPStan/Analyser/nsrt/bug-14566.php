<?php declare(strict_types = 1);

namespace Bug14566;

use function PHPStan\Testing\assertType;

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function foo(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooElse(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		assertType("array{hi: 'hello'}", $test);
	} else {
		assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
	}
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooIsArray(array $test): void {
	if (isset($test['hi']) && is_array($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: int} $test
 */
function fooIsInt(array $test): void {
	if (isset($test['hi']) && is_int($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

class Foo {}

/**
 * @param array{}|array{hi: Foo}|array{hi: 'hello'} $test
 */
function fooInstanceof(array $test): void {
	if (isset($test['hi']) && $test['hi'] instanceof Foo) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: 'world'} $test
 */
function fooStrictComparison(array $test): void {
	if (isset($test['hi']) && $test['hi'] === 'hello') {
		return;
	}
	assertType("array{}|array{hi: 'world'}", $test);
}

/**
 * BooleanOr truthy: logically equivalent to falsy branch of && version
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooBooleanOr(array $test): void {
	if (!isset($test['hi']) || !is_string($test['hi'])) {
		assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
	} else {
		assertType("array{hi: 'hello'}", $test);
	}
}
