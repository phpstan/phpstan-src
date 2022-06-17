<?php

namespace Bug7484;

/**
 * @template K of int|string
 * @template V
 * @param iterable<K, V> $iterable
 * @return iterable<K, V>
 */
function changeKeyCase(
	iterable $iterable,
	int $case = CASE_LOWER
): iterable {
	$callable = $case === CASE_LOWER ? 'strtolower' : 'strtoupper';
	foreach ($iterable as $key => $value) {
		if (is_string($key)) {
			$key = $callable($key);
		}

		yield $key => $value;
	}
}
