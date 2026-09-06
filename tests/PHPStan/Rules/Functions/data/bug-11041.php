<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug11041;

/**
 * @template TKey
 * @template TValue
 */
class Collection
{

	/** @var array<TKey, TValue> */
	private array $items;

	/** @param array<TKey, TValue> $items */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	/**
	 * @param TKey $key
	 * @return TValue
	 */
	public function get($key)
	{
		return $this->items[$key];
	}

}

/** @param Collection<int, string|null> $collection */
function testFunc(Collection $collection): void
{
}

$collection = new Collection([0 => 'foo', 1 => 'bar', 2 => null, 3 => 'baz']);

testFunc($collection);
