<?php declare(strict_types = 1);

/**
 * Maps function parameters to valid constant values.
 *
 * Structure: function name => parameter name => list of valid constant names.
 * The 'int' type in the corresponding functionMap.php parameter type
 * is replaced with a union of the specified constant values.
 */

return [
	'array_multisort' => [
		'array1_sort_order' => ['SORT_ASC', 'SORT_DESC', 'SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL', 'SORT_FLAG_CASE'],
		'array1_sort_flags' => ['SORT_ASC', 'SORT_DESC', 'SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL', 'SORT_FLAG_CASE'],
		'args' => ['SORT_ASC', 'SORT_DESC', 'SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL', 'SORT_FLAG_CASE'],
	],
];
