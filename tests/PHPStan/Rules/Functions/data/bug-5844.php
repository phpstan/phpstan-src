<?php

namespace Bug5844;

/**
 * @template T of string|int
 * @param T $value
 * @return T
 */
function value($value)
{
	if (is_string($value)) {
		return $value;
	}

	return $value;
}
