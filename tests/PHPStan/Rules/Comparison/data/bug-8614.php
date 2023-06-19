<?php // lint >= 8.1

namespace Bug8614;

/**
 * @param int|float|bool|string|object|mixed[] $value
 */
function stringify(int|float|bool|string|object|array $value): string
{
	return match (gettype($value)) {
		'integer', 'double', 'boolean', 'string' => (string) $value,
		'object', 'array' => var_export($value, true),
	};
}
