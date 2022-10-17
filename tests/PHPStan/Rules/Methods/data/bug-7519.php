<?php declare(strict_types=1);

namespace Bug7519;

use FilterIterator;

/**
 * @template TKey
 * @template T
 * @template TIterator as Iterator<TKey, T>
 *
 * @extends FilterIterator<TKey, T, TIterator>
 */
class A extends FilterIterator {
	public function accept(): bool {
		return true;
	}

	public function key() {
		$key = parent::key();

		return $key;
	}

	public function current() {
		$current = parent::current();

		return $current;
	}
}
