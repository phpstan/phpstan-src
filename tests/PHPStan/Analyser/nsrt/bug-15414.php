<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15414;

use function PHPStan\Testing\assertType;

/**
 * @template TValue
 * @implements \IteratorAggregate<int, TValue>
 */
final class Coll implements \IteratorAggregate
{
	/** @param list<TValue> $items */
	public function __construct(private array $items = [])
	{
	}

	/** @return \ArrayIterator<int, TValue> */
	#[\Override]
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	/**
	 * @template TMergeValue
	 * @param iterable<TMergeValue> $items
	 * @return Coll<TValue|TMergeValue>
	 */
	public function merge(iterable $items): Coll
	{
		return new Coll();
	}

	/**
	 * @param (callable(TValue): bool)|TValue $callback
	 * @return Coll<TValue>
	 */
	public function reject($callback): Coll
	{
		return $this;
	}
}

final class Field
{
	public bool $hidden = false;
}

function isExcluded(Field $field): bool
{
	return $field->hidden;
}

/**
 * @param Coll<Field> $a
 * @param Coll<Field> $b
 */
function test(Coll $a, Coll $b): void
{
	assertType('Bug15414\\Coll<Bug15414\\Field>', $a->merge($b)->reject(isExcluded(...)));
	assertType('Bug15414\\Coll<Bug15414\\Field>', $a->merge($b)->reject(static fn (Field $f): bool => isExcluded($f)));
	assertType('Bug15414\\Coll<Bug15414\\Field>', $a->reject(isExcluded(...)));
}
