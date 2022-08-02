<?php declare(strict_types = 1);

namespace Bug6938;

/**
 * @param string|array<int,string> $value
 */
function someFunction($value): void
{
	if (is_string($value)) {
		$value = [$value];
	} elseif (is_array($value)) {
		// If given an array, filter out anything that isn't a string.
		$value = array_filter($value, 'is_string');
	}

	if (! is_array($value)) {
		throw new RuntimeException('Invalid argument type for $value');
	}
}
