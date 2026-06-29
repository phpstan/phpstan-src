<?php declare(strict_types = 1);

namespace Bug14874;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $a
 */
function test(array $a): bool {
	if (isset($a['foo']) && !is_array($a['foo']))
		return false;
	if (array_key_exists('foo', $a)) {
		assertType('array<mixed, mixed>|null', $a['foo']);
		return $a['foo'] === null; // possible
	}
	return false;
}

/**
 * @param array<mixed> $a
 */
function testIs(array $a): void {
	if (isset($a['foo']) && !is_int($a['foo']))
		return;
	if (array_key_exists('foo', $a)) {
		assertType('int|null', $a['foo']);
	}
}
