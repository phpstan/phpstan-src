<?php declare(strict_types = 1);

namespace Bug15321;

use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @template K of string|int|object
 * @template V
 */
interface Map
{
	/** @return (K is array-key ? array<K, V> : array<array-key, V>) */
	public function toArray(): array;

	/** @return array<K, V> */
	public function toArrayPlain(): array;
}

/** @param Map<string, int> $map */
function test(Map $map): void
{
	assertType('array<string, int>', $map->toArray());
	assertType('array<string, int>', $map->toArrayPlain());
}

/**
 * @param Map<int, string> $intKeys
 * @param Map<stdClass, string> $objectKeys
 */
function otherKeys(Map $intKeys, Map $objectKeys): void
{
	assertType('array<int, string>', $intKeys->toArray());
	assertType('array<int, string>', $intKeys->toArrayPlain());
	assertType('array<string>', $objectKeys->toArray());
}
