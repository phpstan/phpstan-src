<?php declare(strict_types = 1); // onlyif PHP_VERSION_ID >= 80100

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

/**
 * @param array{page?: int, search?: string} $settings
 */
function test2(array $settings = []): bool {
	$copy = ['page' => 1, ...$settings];
	assertType('array{page: int, search?: string}', $copy);
	assertType('array{page?: int, search?: string}', $settings);
	return isset($copy['search']);
}
