<?php declare(strict_types = 1);

namespace Bug7776;

use function PHPStan\Testing\assertType;

/**
 * @param array{page?: int, search?: string} $settings
 */
function test(array $settings = []): bool {
	$copy = [...$settings];
	assertType('array{page?: int, search?: string}', $copy);
	assertType('array{page?: int, search?: string}', $settings);
	return isset($copy['search']);
}
