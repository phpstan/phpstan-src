<?php

declare(strict_types = 1);

namespace Bug11918;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, list<mixed>|string|false> $options
 */
function narrowMaybeSetArrayKey(array $options): void
{
	if (array_key_exists('a', $options) && !is_string($options['a'])) {
		exit(1);
	}

	// At this point: either 'a' doesn't exist in $options, or it's a string
	assertType("array<string, list<mixed>|string|false>", $options);
	assertType('string', $options['a'] ?? 'fallback');
}

/**
 * @param array<string, list<mixed>|string|false> $options
 */
function narrowMaybeSetArrayKeyIsInt(array $options): void
{
	if (array_key_exists('b', $options) && !is_int($options['b'])) {
		exit(1);
	}

	assertType("array<string, list<mixed>|string|false>", $options);
}

/**
 * @param array<string, int|string|bool> $data
 */
function narrowWithIsset(array $data): void
{
	if (isset($data['key']) && !is_string($data['key'])) {
		exit(1);
	}

	// After: 'key' either doesn't exist or is string (possibly non-falsy substring)
	assertType('string', $data['key'] ?? 'default');
}

/**
 * @param array<string, int|string|bool> $data
 */
function narrowWithNegatedOr(array $data): void
{
	if (!array_key_exists('x', $data) || is_string($data['x'])) {
		// Inside here: either 'x' doesn't exist or it's string
		assertType('string', $data['x'] ?? 'default');
	}
}

/**
 * @param array<string, mixed> $data
 */
function narrowWithInstanceof(array $data): void
{
	if (array_key_exists('obj', $data) && !$data['obj'] instanceof \stdClass) {
		exit(1);
	}

	assertType('stdClass', $data['obj'] ?? new \stdClass());
}
