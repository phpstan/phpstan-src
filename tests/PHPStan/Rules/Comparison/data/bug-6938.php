<?php

namespace Bug6938;

/**
 * @param string|array<int,string> $value
 */
function myFunction($value): void
{
	if (is_string($value)) {
		$value = [$value];
	} elseif (is_array($value)) {
		// If given an array, filter out anything that isn't a string.
		$value = array_filter($value, 'is_string');
	}

	if (! is_array($value)) {
		throw new \DomainException('Invalid argument type for $value');
	}

	// Now we know that $value is either a string or an array of strings.
}
