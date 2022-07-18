<?php declare(strict_types = 1);

use function PHPStan\Testing\assertType;

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

		assertType('TKey (class A, argument)', $key);

		return $key;
	}

	public function current() {
		$current = parent::current();

		assertType('T (class A, argument)', $current);

		return $current;
	}
}
