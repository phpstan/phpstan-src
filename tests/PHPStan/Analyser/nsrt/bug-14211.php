<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14211;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, int> $array
 */
function foo(string $key, array $array): void {
	if (array_key_exists($key, $array)) {
		$value = $array[$key];
		assertType('int', $value);
	} else {
		$value = null;
	}

	assertType('int|null', $value);

	if ($value !== null) {
		assertType('bool', array_key_exists($key, $array)); // should be true, see phpstan/phpstan#14211
	}
}
