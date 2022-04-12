<?php declare(strict_types = 1);

namespace Bug5592;

/**
 * @param \Ds\Map<\Ds\Hashable, numeric-string> $map
 * @return numeric-string
 */
function mapGet(\Ds\Map $map, \Ds\Hashable $key): string
{
	return $map->get($key, '0');
}
