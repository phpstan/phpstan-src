<?php

declare(strict_types = 1);

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
	assertType("array{0: 42, 1?: 42}", $test['hi']);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function fooOr(array $test): void {
	if (!isset($test['hi']) || !is_string($test['hi'])) {
		assertType("array{}|array{hi: array{0: 42, 1?: 42}}", $test);
		return;
	}
	assertType("array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 42}|array{hi: 'hello'} $test
 */
function fooIsInt(array $test): void {
	if (isset($test['hi']) && is_int($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42}} $test
 */
function fooIsArray(array $test): void {
	if (isset($test['hi']) && is_array($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: \stdClass}|array{hi: 'hello'} $test
 */
function fooInstanceof(array $test): void {
	if (isset($test['hi']) && $test['hi'] instanceof \stdClass) {
		return;
	}
	assertType("array{}|array{hi: 'hello'}", $test);
}

/**
 * @param array{}|array{hi: string|int}|array{hi: float} $test
 */
function fooPartialOverlap(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	assertType("array{}|array{hi: float}|array{hi: int}", $test);
}

/**
 * @param array{}|array{hi: string|int}|array{hi: float} $test
 */
function fooPartialOverlapOr(array $test): void {
	if (!isset($test['hi']) || !is_string($test['hi'])) {
		assertType("array{}|array{hi: float}|array{hi: int}", $test);
		return;
	}
	assertType("array{hi: string}", $test);
}

class FooContainer {
	/** @var \stdClass|string */
	public $x;
	/** @var \stdClass|int */
	public $y;
}

function fooPropertyFetchInstanceof(FooContainer $c): void {
	if ($c->x instanceof \stdClass && $c->y instanceof \stdClass) {
		return;
	}
	if ($c->x instanceof \stdClass) {
		assertType('int', $c->y);
	}
}

function fooPropertyFetchInstanceofOr(FooContainer $c): void {
	if (!$c->x instanceof \stdClass || !$c->y instanceof \stdClass) {
		if ($c->x instanceof \stdClass) {
			assertType('int', $c->y);
		}
		return;
	}
	assertType('stdClass', $c->x);
	assertType('stdClass', $c->y);
}
