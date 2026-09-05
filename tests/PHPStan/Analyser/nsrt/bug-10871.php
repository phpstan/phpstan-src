<?php

declare(strict_types = 1);

namespace Bug10871;

use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
interface Map
{

	/**
	 * @template TOtherKey of array-key
	 * @template TOtherValue
	 * @param iterable<TOtherKey, TOtherValue> ...$iterables
	 * @return self<TKey|TOtherKey, TValue|TOtherValue>
	 */
	public function merge(iterable ...$iterables): self;

}

/**
 * @param Map<string, int> $map
 */
function test(Map $map, int $int, bool $bool): void
{
	assertType('Bug10871\Map<int|string, bool|int>', $map->merge([$int => $bool]));
	assertType('Bug10871\Map<int|string, bool|int|stdClass>', $map->merge([$int => $bool], ['test' => new stdClass()]));
}
