<?php // onlyif PHP_VERSION_ID >= 80100

namespace Bug6695;

use function PHPStan\Testing\assertType;

enum Foo: int
{
	case BAR = 1;
	case BAZ = 2;

	public function toCollection(): void
	{
		assertType('Bug6695\Collection<int, Bug6695\Foo::BAR|Bug6695\Foo::BAZ>', $this->collect(self::cases()));
	}

	/**
	 * Create a collection from the given value.
	 *
	 * @template TKey of array-key
	 * @template TValue
	 *
	 * @param  iterable<TKey, TValue>  $value
	 * @return Collection<TKey, TValue>
	 */
	function collect($value): Collection
	{
		return new Collection($value);
	}

}

/**
 * @template TKey of array-key
 * @template TValue
 *
 */
class Collection
{

	/**
	 * The items contained in the collection.
	 *
	 * @var iterable<TKey, TValue>
	 */
	protected $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param  iterable<TKey, TValue>  $items
	 * @return void
	 */
	public function __construct($items = [])
	{
		$this->items = $items;
	}
}
