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
    if ($input instanceof Traversable) {
        return iterator_to_array($input);
    }

    return $input;
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
