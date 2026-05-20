<?php

namespace ConditionalArrayKeyExists;

use function PHPStan\Testing\assertType;

/** @param array<string, mixed> $options */
function apply(array $options): void
{
	$range = [];
	if (isset($options['min_range'])) {
		$range['min'] = 1;
	}
	if (isset($options['max_range'])) {
		$range['max'] = 2;
	}

	// $range can be {}, {min}, {max}, or {min, max}
	assertType('array{min?: 1, max?: 2}', $range);

	if (array_key_exists('min', $range) || array_key_exists('max', $range)) {
		// reachable: either key could be set.
		assertType('non-empty-array{min?: 1, max?: 2}', $range);
	}
}
