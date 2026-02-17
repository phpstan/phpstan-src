<?php

namespace Bug11101;

/**
 * @param array<mixed> $array
 */
function doFoo(array $array): void
{
	// These should all be reported as having no effect
	array_filter($array, 'is_string'); // line 11
	array_map('is_string', $array); // line 12
	array_reduce($array, function ($carry, $item) { // line 13
		return $carry + $item;
	}, 0);

	// These are fine - using the return value
	$a = array_filter($array, 'is_string');
	$b = array_map('is_string', $array);
	$c = array_reduce($array, function ($carry, $item) {
		return $carry + $item;
	}, 0);
}
