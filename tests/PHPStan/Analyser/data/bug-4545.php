<?php

namespace Bug4545;

use Closure;
use Ds\Hashable;
use Ds\Map;
use Ds\Set;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * Returns keys which either exist only in one of the maps or exist in both but their associated values are not equal.
	 *
	 * @template TKey of Hashable
	 * @template TValue1
	 * @template TValue2
	 *
	 * @param Map<TKey, TValue1> $firstMap
	 * @param Map<TKey, TValue2> $secondMap
	 * @param Closure(TValue1, TValue2): bool $comparator
	 *
	 * @return Set<TKey>
	 */
	function compareMaps(Map $firstMap, Map $secondMap, Closure $comparator): Set
	{
		$firstMapKeys = $firstMap->keys();
		$secondMapKeys = $secondMap->keys();
		$keys = $firstMapKeys->xor($secondMapKeys);
		$intersect = $firstMapKeys->intersect($secondMapKeys);
		foreach ($intersect as $key) {
			assertType('TValue1 (method Bug4545\Foo::compareMaps(), argument)', $firstMap->get($key));
			assertType('TValue2 (method Bug4545\Foo::compareMaps(), argument)', $secondMap->get($key));
			assertType('1|TValue2 (method Bug4545\Foo::compareMaps(), argument)', $secondMap->get($key, 1));
		}

		return $keys;
	}

}

