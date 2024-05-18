<?php

namespace Bug10732;

/**
 * @template-covariant TValue
 */
class Collection
{
	/**
	 * Create a new collection.
	 *
	 * @param list<TValue> $items
	 * @return void
	 */
	public function __construct(protected array $items = []) {}

	/**
	 * Run a map over each of the items.
	 *
	 * @template TMapValue
	 *
	 * @param  callable(TValue): TMapValue  $callback
	 * @return static<TMapValue>
	 */
	public function map(callable $callback)
	{
		return new self(array_map($callback, $this->items));
	}
}

/**
 * I'd expect this to work?
 *
 * @param Collection<array<string,  mixed>> $collection
 * @return Collection<array<string,  mixed>>
 */
function current(Collection $collection): Collection
{
	return $collection->map(fn(array $item) => $item);
}

/**
 * Removing the Typehint works
 *
 * @param Collection<array<string,  mixed>> $collection
 * @return Collection<array<string,  mixed>>
 */
function removeTypeHint(Collection $collection): Collection
{
	return $collection->map(fn($item) => $item);
}

/**
 * Typehint works for simple type
 *
 * @param Collection<string> $collection
 * @return Collection<string>
 */
function simplerType(Collection $collection): Collection
{
	return $collection->map(fn(string $item) => $item);
}

/**
 * Typehint works for arrays
 *
 * @param array<string, array<string,  mixed>> $collection
 * @return array<string, array<string,  mixed>>
 */
function useArraysInstead(array $collection): array
{
	return array_map(
		fn(array $item) => $item,
		$collection,
	);
}
