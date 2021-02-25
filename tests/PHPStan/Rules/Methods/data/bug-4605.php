<?php

namespace Bug4605;

/**
 * @phpstan-template TKey
 * @psalm-template TKey of array-key
 * @psalm-template T
 * @template-extends IteratorAggregate<TKey, T>
 * @template-extends ArrayAccess<TKey|null, T>
 */
interface Collection extends \Countable, \IteratorAggregate, \ArrayAccess {}

class Boo {
	/**
	 * @param Collection<array-key, string> $collection
	 * @return Collection<array-key, string>
	 */
	public function foo(Collection $collection): Collection
	{
		return $collection;
	}

	/**
	 * @param Collection<int, string> $collection
	 * @return Collection<int, string>
	 */
	public function boo(Collection $collection): Collection
	{
		return $collection;
	}
}
