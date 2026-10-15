<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11507;

/**
 * @template TKey of array-key
 *
 * @template TValue
 */
class Collection
{
	/**
	* @param array<TKey, TValue> $items
	*/ 
	public function __construct(
		public array $items,
	) {}

	 /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return static<TKey, TMapValue>
     */
	public function map(callable $callback) 
	{
		$keys = array_keys($this->items);

		$items = array_map($callback, $this->items, $keys);

        $result = array_combine($keys, $items);

		return new self($result);
	}
}

/**
 * @param  Collection<int, non-empty-array<string, string>>  $collection
 * @return Collection<int, non-empty-array<string, string>>
 */
function foo(Collection $collection)
{
	return $collection->map(function (array $item) {
		$item['foo'] = '100';

		return $item;
	});
}
