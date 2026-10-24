<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15476;

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

	/** @return Box<int, Item> */
	public function deadBranch(Item $item): Box
	{
		if ($item === null) {
			return $this->factory->box();
		}

		return $this->factory->box([$item]);
	}

	/** @return Box<int, Item> */
	public function liveBranch(?Item $item): Box
	{
		if ($item === null) {
			return $this->factory->box();
		}

		return $this->factory->box([$item]);
	}

	/** @return Box<int, Item> */
	public function deadElseif(Item $item, bool $b): Box
	{
		if ($b) {
			return $this->factory->box([$item]);
		} elseif ($item === null) {
			return $this->factory->box();
		}

		return $this->factory->box([$item]);
	}

	/** @return Box<int, Item> */
	public function deadElse(Item $item): Box
	{
		if ($item !== null) {
			return $this->factory->box([$item]);
		} else {
			return $this->factory->box();
		}
	}

	/** @return Box<int, Item> */
	public function deadElseAfterAlwaysTrueElseif(Item $item, bool $b): Box
	{
		if ($b) {
			return $this->factory->box([$item]);
		} elseif ($item !== null) {
			return $this->factory->box([$item]);
		} else {
			return $this->factory->box();
		}
	}

	/** @return Box<int, Item> */
	public function deadNestedBranch(Item $item, bool $b): Box
	{
		if ($b) {
			if ($item === null) {
				return $this->factory->box();
			}
		}

		return $this->factory->box([$item]);
	}

}
