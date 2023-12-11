<?php

namespace Bug5592;

/**
 * @param \Ds\Map<\Ds\Hashable, numeric-string> $map
 * @return numeric-string
 */
function mapGet(\Ds\Map $map, \Ds\Hashable $key): string
{
	return $map->get($key, '0');
}

/**
 * @template TDefault
 * @param TDefault $default
 * @return numeric-string|TDefault
 */
function getFooOrDefault($default) {
	if ((bool) random_int(0, 1)) {
		/** @var numeric-string */
		$foo = '5';
		return $foo;
	} else {
		return $default;
	}
}

function doStuff(): int
{
	/**
	 * @var \Ds\Map<string, positive-int>
	 */
	$map = new \Ds\Map();

	return $map->get('foo', 1);
}

/**
 * @return numeric-string
 */
function doStuff1(): string {
	/** @var numeric-string */
	$foo = '12';
	return getFooOrDefault($foo);
}

/**
 * @return numeric-string
 */
function doStuff2(): string {
	return getFooOrDefault('12');
}
