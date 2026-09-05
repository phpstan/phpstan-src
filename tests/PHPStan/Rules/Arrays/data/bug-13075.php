<?php declare(strict_types = 1);

namespace Bug13075;

// In all the examples the caller ensures there's at least one key present in
// the $data parameter and we want to give that information to phpstan.

/**
 * @param array{a?: string, b?: string} $data
 */
function works(array $data): string {
	if (isset($data['a'])) {
		return $data['a'];
	}
	assert(isset($data['b']));
	return $data['b'];
}

/**
 * @param array{a?: string, b?: string} $data
 */
function fail1(array $data): string {
	assert(isset($data['a']) || isset($data['b']));
	return $data['a'] ?? $data['b'];
}

/**
 * @param array{a?: string, b?: string} $data
 */
function fail2(array $data): string {
	assert(array_key_exists('a', $data) || array_key_exists('b', $data));
	return $data['a'] ?? $data['b'];
}

/**
 * @param array{a?: string, b?: string, c?: string} $data
 */
function fail3(array $data): string {
	assert((bool) array_intersect_key($data, array_flip(['a', 'b', 'c'])));
	return $data['a'] ?? $data['b'] ?? $data['c'];
}
