<?php declare(strict_types = 1);

namespace PHPStan\Command\Bisect;

use InvalidArgumentException;
use function array_slice;
use function ceil;
use function count;
use function log;

final class BinarySearch
{

	/**
	 * @template T
	 * @param list<T> $items Items ordered from oldest to newest (at least 2)
	 * @return BinarySearchStep<T>
	 */
	public static function getStep(array $items): BinarySearchStep
	{
		$count = count($items);
		if ($count < 2) {
			throw new InvalidArgumentException('Binary search requires at least 2 items.');
		}

		$mid = (int) (($count - 1) / 2);

		return new BinarySearchStep(
			$items[$mid],
			array_slice($items, $mid + 1),
			array_slice($items, 0, $mid + 1),
			(int) ceil(log($count, 2)),
		);
	}

}
