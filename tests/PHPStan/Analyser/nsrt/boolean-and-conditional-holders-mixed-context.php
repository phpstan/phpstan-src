<?php declare(strict_types = 1);

namespace BooleanAndConditionalHoldersMixedContext;

use function PHPStan\Testing\assertType;

// Comparing a `&&` condition with `=== true` and taking the `else` branch
// specifies the inner BooleanAnd in a mixed truthy-and-false context (both
// truthy() and false() hold). When an arm's falsey narrowing is empty there
// (e.g. isset()/array_key_exists() on an array dim fetch) the cross-kind
// conditional holders must still be derived from that arm's truthy narrowing.

/**
 * @param array<string, mixed> $data
 */
function issetVarKey(array $data, string $key): void
{
	if ((isset($data[$key]) && !is_string($data[$key])) === true) {
		return;
	}

	assertType('string', $data[$key] ?? 'fallback');
}

/**
 * @param array<string, mixed> $data
 */
function issetConstKey(array $data): void
{
	if ((isset($data['k']) && !is_string($data['k'])) === true) {
		return;
	}

	assertType('string', $data['k'] ?? 'fallback');
}

/**
 * @param array<string, mixed> $data
 */
function arrayKeyExistsMixed(array $data): void
{
	if ((array_key_exists('k', $data) && !is_string($data['k'])) === true) {
		return;
	}

	assertType('string', $data['k'] ?? 'fallback');
}

/**
 * @param mixed $y
 */
function simpleBoolMixed(bool $a, $y): void
{
	if (($a && !is_string($y)) === true) {
		return;
	}

	if ($a) {
		assertType('string', $y);
	}
}
