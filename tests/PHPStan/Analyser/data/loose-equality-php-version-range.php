<?php

namespace LooseEqualityPhpVersionRange;

use function PHPStan\Testing\assertType;

/**
 * @param int|string $key
 * @param array<int|string, mixed> $config
 */
function doFoo($key, int $int, float $float, string $string, array $config): void
{
	// 0 == 'abc' on PHP 7, not on PHP 8
	assertType('bool', 0 == 'abc');
	assertType('bool', $int == 'abc');
	// INF == 'INF' on PHP 8, not on PHP 7
	assertType('bool', INF == 'INF');
	assertType('true', 1 == '1');
	assertType('false', 1 == 'abc');

	if ($key == 'abc') {
		assertType("0|'abc'", $key);
	} else {
		// 0 is in the false branch on PHP 8
		assertType('int|string', $key);
	}

	if ($int == 'abc') {
		assertType('0', $int);
	} else {
		assertType('int', $int);
	}

	if ($float == 'INF') {
		assertType('0.0|INF', $float);
	}

	if (in_array($key, ['a', 'b'])) {
		assertType("0|'a'|'b'", $key);
	}

	foreach ($config as $type => $args) {
		switch ($type) {
			case 'options':
				break;
			case 'widgets':
				assertType("0|'widgets'", $type);
				break;
		}
	}
}

/**
 * @param int|string $key
 */
function narrowedByGuard($key, int $int): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('false', 0 == 'abc');
		assertType('true', INF == 'INF');
		if ($key == 'abc') {
			assertType("'abc'", $key);
		}
		if ($int == 'abc') {
			assertType('*NEVER*', $int);
		}
	} else {
		assertType('true', 0 == 'abc');
		assertType('false', INF == 'INF');
		if ($key == 'abc') {
			assertType("0|'abc'", $key);
		} else {
			assertType('int<min, -1>|int<1, max>|string', $key);
		}
	}
}
