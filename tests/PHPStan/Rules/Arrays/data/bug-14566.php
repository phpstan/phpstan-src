<?php declare(strict_types = 1);

namespace Bug14566;

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function foo(array $test): void {
	if (isset($test['hi']) && is_string($test['hi'])) {
		return;
	}
	$test['hi'][] = 42;
}

/**
 * @param array{}|array{hi: 'hello'}|array{hi: array{0: 42, 1?: 42}} $test
 */
function foo2(array $test): void {
	if (\is_string($test['hi'] ?? null)) {
		return;
	}
	$test['hi'][] = 42;
}
