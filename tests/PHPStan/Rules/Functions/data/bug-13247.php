<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13247;

use Traversable;

/**
 * @param array<int, int>|Traversable<int, int> $input
 *
 * @return array<int, int>
 */
function as_array(array|Traversable $input): array {
	return iter_as_array($input);
}

/**
 * @param iterable<int, int> $input
 *
 * @return array<int, int>
 */
function iter_as_array(iterable $input): array {
	return as_array($input);
}

/**
 * @param array|Traversable $input
 *
 * @return array
 */
function as_array2(array|Traversable $input): array {
	return iter_as_array2($input);
}

/**
 * @param iterable $input
 *
 * @return array
 */
function iter_as_array2(iterable $input): array {
	return as_array2($input);
}
