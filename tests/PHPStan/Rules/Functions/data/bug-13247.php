<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13247;

use Traversable;

/**
 * @template K as array-key
 * @template V
 *
 * @param array<K, V>|Traversable<K, V> $input
 *
 * @return array<K, V>
 */
function as_array(array|Traversable $input): array {
	return iter_as_array($input);
}

/**
 * @template K as array-key
 * @template V
 *
 * @param iterable<K, V> $input
 *
 * @return array<K, V>
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

/**
 * @param array<int, int>|Traversable<int, int> $input
 *
 * @return array<int, int>
 */
function as_array3(array|Traversable $input): array {
	return iter_as_array3($input);
}

/**
 * @param iterable<int, int> $input
 *
 * @return array<int, int>
 */
function iter_as_array3(iterable $input): array {
	return as_array3($input);
}

/**
 * @phpstan-template T of iterable<int, int>
 *
 * @param T $input
 *
 * @return mixed
 */
function test1(iterable $input) {
	test2($input);
	iter_as_array($input);

	return as_array($input);
}

/**
 * @phpstan-template U of Traversable<int, int>|array<int, int>
 *
 * @param U $input
 *
 * @return mixed
 */
function test2($input) {
	test1($input);
	iter_as_array($input);

	return as_array($input);
}
