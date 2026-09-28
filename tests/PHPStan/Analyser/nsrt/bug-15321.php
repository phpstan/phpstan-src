<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15321;

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

	/** @return non-empty-array<K, V> */
	public function toNonEmptyArray(): array;

	/** @param array<K, V> $array */
	public function merge(array $array): void;
}

/** @param Map<string, int> $map */
function test(Map $map): void
{
	assertType('array<string, int>', $map->toArray());
	assertType('array<string, int>', $map->toArrayPlain());
	assertType('non-empty-array<string, int>', $map->toNonEmptyArray());
}

/**
 * @template K of string|int|object
 * @template V
 */
final class ArrayMap
{

	/** @var array<K, V> */
	private array $items;

	/**
	 * @param array<K, V> $promoted
	 * @param array<K, V> $items
	 */
	public function __construct(private array $promoted, array $items)
	{
		$this->items = $items;
	}

	/** @param array<K, V> $array */
	public function merge(array $array): void
	{
		assertType('array<K of int|string (class Bug15321\ArrayMap, argument), V (class Bug15321\ArrayMap, argument)>', $array);
		assertType('array<K of int|string (class Bug15321\ArrayMap, argument), V (class Bug15321\ArrayMap, argument)>', $this->items);
		assertType('array<K of int|string (class Bug15321\ArrayMap, argument), V (class Bug15321\ArrayMap, argument)>', $this->promoted);
	}

	/** @return array<K, V> */
	public function toArray(): array
	{
		return $this->items;
	}

}

/** @param ArrayMap<int, string> $map */
function testArrayMap(ArrayMap $map): void
{
	assertType('array<int, string>', $map->toArray());
}

/**
 * @template K of string|int|object
 * @template V
 */
trait MapTrait
{

	/** @return array<K, V> */
	public function traitToArray(): array
	{
		return [];
	}

}

/**
 * @template K of string|int|object
 * @template V
 * @extends Map<K, V>
 */
interface SortedMap extends Map
{

	/** @return array<K, V> */
	public function toSortedArray(): array;

}

/**
 * @template K of string|int|object
 * @template V
 */
abstract class MapWithTrait
{

	/** @use MapTrait<K, V> */
	use MapTrait;

}

/**
 * @param SortedMap<string, int> $sortedMap
 * @param MapWithTrait<string, int> $mapWithTrait
 */
function testInherited(SortedMap $sortedMap, MapWithTrait $mapWithTrait): void
{
	assertType('array<string, int>', $sortedMap->toArray());
	assertType('array<string, int>', $sortedMap->toArrayPlain());
	assertType('array<string, int>', $sortedMap->toSortedArray());
	assertType('array<string, int>', $mapWithTrait->traitToArray());
}
