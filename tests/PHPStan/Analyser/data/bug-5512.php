<?php

namespace Bug5512;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 * @phpstan-consistent-templates
 */
class Collection {
	/**
	 * @var array<TKey, TValue>
	 */
	protected $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param  array<TKey, TValue>  $items
	 * @return void
	 */
	public function __construct($items)
	{
		$this->items = $items;
	}

	/**
	 * @template TMakeKey of array-key
	 * @template TMakeValue
	 *
	 * @param  array<TMakeKey, TMakeValue>  $items
	 * @return static<TMakeKey, TMakeValue>
	 */
	public static function make($items)
	{
		return new static($items);
	}
}


/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends Collection<TKey, TValue>
 */
class CustomCollection extends Collection {}


/** @param CustomCollection<int, string> $collection */
function foo(CustomCollection $collection) {
	//assertType('Bug5512\CustomCollection<int, int>', CustomCollection::make([1]));
	assertType('Bug5512\CustomCollection<int, int>', $collection::make([1,2,3,4]));
}
