<?php declare(strict_types=1);

namespace Bug7519;

use function PHPStan\Testing\assertType;
use FilterIterator;

/**
 * @template TKey
 * @template T
 * @template TIterator as Iterator<TKey, T>
 *
 * @extends FilterIterator<TKey, T, TIterator>
 */
class A extends FilterIterator
{
	public function accept(): bool
	{
		return true;
	}

	public function key()
	{
		$key = parent::key();

		assertType('TKey (class A, argument)', $key);

		return $key;
	}

	public function current()
	{
		$current = parent::current();

		assertType('T (class A, argument)', $current);

		return $current;
	}
}

// Counter exemple: https://3v4l.org/MXHWf#v8.1.7
