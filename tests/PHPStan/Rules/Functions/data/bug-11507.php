<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11507;

/**
 * @template TValue
 */
class Collection
{
	/**
	* @param array<int, TValue> $items
	*/
	public function __construct(
		public array $items,
	) {}

	 /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, int=): TMapValue  $callback
     * @return Collection<TMapValue>
     */
	public function map(callable $callback): Collection
	{
		$keys = array_keys($this->items);

		$items = array_map($callback, $this->items);

        $result = array_combine($keys, $items);

		return new self($result);
	}
}

/**
 * @param  Collection<non-empty-array<string>>  $collection
 * @return Collection<non-empty-array<string>>
 */
function foo(Collection $collection): Collection
{
	return $collection->map(function (array $item) {
		$item['foo'] = '100';

		return $item;
	});
}
