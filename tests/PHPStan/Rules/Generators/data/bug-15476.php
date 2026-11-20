<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15476Generators;

/**
 * @template TKey of array-key
 * @template TValue
 */
final class Box
{
}

interface Factory
{
	/**
	 * @template TKey of array-key
	 * @template TValue
	 * @param iterable<TKey, TValue> $value
	 * @return Box<TKey, TValue>
	 */
	public function box(iterable $value = []): Box;
}

final class Item
{
}

final class Example
{
	public function __construct(private Factory $factory)
	{
	}

	/** @return \Generator<int, Box<int, Item>> */
	public function deadBranch(Item $item): \Generator
	{
		if ($item === null) {
			yield $this->factory->box();
		}

		yield $this->factory->box([$item]);
	}

	/** @return \Generator<int, Box<int, Item>> */
	public function deadElse(Item $item): \Generator
	{
		if ($item !== null) {
			yield $this->factory->box([$item]);
		} else {
			yield $this->factory->box();
		}
	}

}
