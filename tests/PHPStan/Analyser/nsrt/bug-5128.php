<?php declare(strict_types = 1);

namespace Bug5128;

use function PHPStan\Testing\assertType;

/**
 * @param array{a: string}|array{b: string} $array
 */
function a(array $array): string {
	if (isset($array['a'])) {
		assertType('array{a: string}', $array);
		return $array['a'];
	}

	assertType('array{b: string}', $array);
	return $array['b'];
}
