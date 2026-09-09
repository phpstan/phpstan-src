<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8268;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 */
class Collection
{

	/**
	 * @param TKey|null $offset
	 */
	public function set(int|string|null $offset): void
	{
		assertType('TKey of (int|string) (class Bug8268\Collection, argument)|null', $offset);
		if ($offset === null) {
			return;
		}

		assertType('TKey of (int|string) (class Bug8268\Collection, argument)', $offset);
	}

}
