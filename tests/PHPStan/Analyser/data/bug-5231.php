<?php

namespace Bug5231;

class SomeClass
{

}

// class with bug

/**
 *     annotation must be exists:
 * @method SomeClass[]|SomeParentCollection getSorted()
 */
class SomeParentCollection implements \Iterator
{
	/** @var SomeClass[] */
	protected $collection;

	public function findByName(string $name): ?SomeClass
	{
		foreach ($this->collection as $item) {
			if ((string)$item === $name) {
				return $item;
			}
		}

		return null;
	}

	// must be exists!
	public function existsByKey(string $name): bool
	{
		return $this->findByName($name) !== null;
	}

	public function getSorted(callable $comparator): self
	{
		$sortedCollection = $this->collection;
		usort($sortedCollection, $comparator);

		$filtered = array_values($sortedCollection);

		return new static(...$filtered);
	}

	public function rewind(): void
	{
		reset($this->collection);
	}

	public function current()
	{
		return current($this->collection);
	}

	/**
	 * @return bool|float|int|string|null
	 */
	public function key()
	{
		return key($this->collection);
	}

	/**
	 * @return mixed|void
	 */
	public function next()
	{
		return next($this->collection);
	}

	public function valid(): bool
	{
		return isset($this->collection[key($this->collection)]);
	}
}
