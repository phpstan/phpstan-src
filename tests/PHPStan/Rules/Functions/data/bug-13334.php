<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13334;

/**
 * Example 1 - Inline
 */
$array = [-1, 0, 1, 5, '', 'hi', true, false, null];
$key = array_rand($array, 1);
$value = $array[$key];

$strict = boolval(random_int(0,1));

$result = (
		is_string($value)
		|| (
			! $strict
			&& boolval(
				$value = (
					(! empty($value) && is_numeric($value))
					? str_pad((string) $value, 6, '0', STR_PAD_LEFT)
					: ''
				)
			)
		)
	)
	&& preg_match('/^0*[1-9]+[0-9]*$/', $value) === 1;


/**
 * Example 2 - Using functions
 */
function ensureString(mixed $value): string
{
	return ((! empty($value) && is_numeric($value)) ? str_pad((string) $value, 6, '0', STR_PAD_LEFT) : '');
}

function isNonZeroPaddedString(mixed $value, bool $strict = false): bool
{
	return (
		is_string($value)
		|| (
			! $strict
			&& boolval($value = ensureString($value))
		)
	)
	&& preg_match('/^0*[1-9]+[0-9]*$/', $value) === 1;
}
