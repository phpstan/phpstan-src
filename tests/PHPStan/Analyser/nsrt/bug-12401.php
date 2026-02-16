<?php declare(strict_types = 1);

namespace Bug12401;

use function PHPStan\Testing\assertType;

/**
 * @param array{a: string, b: string}|array{c: string} $data
 */
function test(array $data): void {
	if (isset($data['a'])) {
		assertType('array{a: string, b: string}', $data);
	} else {
		assertType('array{c: string}', $data);
	}

	if (isset($data['a'])) {
		assertType('array{a: string, b: string}', $data);
	} elseif (isset($data['c'])) {
		assertType('array{c: string}', $data);
	} else {
		assertType('*NEVER*', $data);
	}
}
