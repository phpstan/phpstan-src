<?php

namespace Bug9766;

/**
 * @template TKey of array-key
 * @template TItem
 */
abstract class PHPStanBug {
	/**
	 * @param iterable<TKey, TItem> $items
	 */
	public function __construct(
		private iterable $items,
	) {
		// empty
	}

	/**
	 * @return iterable<TKey, TItem>
	 */
	protected function getItems(): iterable {
		return $this->items;
	}
}
