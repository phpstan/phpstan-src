<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug8516;

function validate($value, array $options = null): bool
{
	if (is_int($value)) {
		$options ??= ['options' => ['min_range' => 0]];
		if (filter_var($value, FILTER_VALIDATE_INT, $options) === false) {
			return false;
		}
		// ...
	}
	if (is_string($value)) {
		// ...
	}
	return true;
}
