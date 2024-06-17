<?php declare(strict_types=1);

namespace Bug11161;

/** @template ItemType */
interface Collection
{
	/** @param ItemType $item */
	public function add(mixed $item): void;

	/** @return ItemType|null */
	public function get(int $index): mixed;
}

class Comparator
{
	/**
	 * @param Collection<object> $foo1
	 * @param Collection<covariant object> $foo2
	 */
	public static function compare(Collection $foo1, Collection $foo2): bool
	{
		return $foo1 === $foo2;
	}
}

/**
 * @param Collection<object> $collection
 */
function test(Collection $collection): bool
{
	return Comparator::compare($collection, $collection);
}
