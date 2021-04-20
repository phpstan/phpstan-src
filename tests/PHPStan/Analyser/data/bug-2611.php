<?php

namespace Bug2611;

use function PHPStan\Testing\assertType;

/**
 * @param \Traversable|array $collection
 * @return array
 */
function flatten($collection)
{
	$stack = [$collection];
	$result = [];

	while (!empty($stack)) {
		$item = \array_shift($stack);
		assertType('mixed', $item);

		if (\is_array($item) || $item instanceof \Traversable) {
			foreach ($item as $element) {
				\array_unshift($stack, $element);
			}
		} else {
			\array_unshift($result, $item);
		}
	}

	return $result;
}
